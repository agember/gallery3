<?php
/**
 * Description of wlpg_interpreter
 *
 * @author tkott
 */
class wlpg_xmp extends interpreter {
  /**
   * Microsoft Windows Live Photo Gallery schema namespace
   */
  const XMP_NS_WLPG = "http://ns.microsoft.com/photo/1.0/";
  const XMP_NS_WLPG_ALT = "http://ns.microsoft.com/photo/1.0/";

  protected function extract_title() {
    parent::extract_title();
  }
  protected function extract_description() {
    parent::extract_description();
  }

  protected function extract_rating() {
    if($this->debug) {
      Kohana_Log::add("information", "[XMP Module] Extracting the rating (stars) using the WLPG method.\n");
    }
    try {
      parent::extract_rating();
      
      //If we still haven't found anything, try the appropriate WLPG tags
      if(!$this->rating) {
        $this->rating = $this->xmpReader->getImplodedField("Rating", self::XMP_NS_WLPG);
      }
      if(!$this->rating) {
        $this->rating = $this->xmpReader->getImplodedField("Rating", self::XMP_NS_WLPG_ALT);
      }

      parent::interpret_rating();

    } catch (Exception $e) {
      Kohana_Log::add("error", "[XMP Module] Error adding the rating.\n" .
                      $e->getMessage() . "\n" . $e->getTraceAsString());
    }
  }

  protected function extract_tags() {
    if($this->debug) {
      Kohana_Log::add("information", "[XMP Module] Extracting tags using WLPG.\n");
    }
    try {
      parent::extract_tags();

      $new_tags = $this->xmpReader->getField("LastKeywordXMP", self::XMP_NS_WLPG);
      foreach( $new_tags as $unsplitTag ) {
        $splitTags =  explode('/', $unsplitTag);
        foreach ($splitTags as $tag) {
          $this->tags[$tag] = 0;
          if($this->debug) {
            Kohana_Log::add("information", "[XMP Module] Tag read using {self::XMP_NS_WLPG}: {$tag}.\n");
          }
        }
      }

      //Surprisingly, this is a different search
      $new_tags = $this->xmpReader->getField("LastKeywordXMP", self::XMP_NS_WLPG_ALT);
      foreach( $new_tags as $unsplitTag ) {
        $splitTags =  explode('/', $unsplitTag);
        foreach ($splitTags as $tag) {
          $this->tags[$tag] = 0;
          if($this->debug) {
            Kohana_Log::add("information", "[XMP Module] Tag read using {self::XMP_NS_WLPG_ALT}: {$tag}.\n");
          }
        }
      }

    } catch (Exception $e) {
      Kohana_Log::add("error", "[XMP Module] Error adding tag: $tag\n" .
                  $e->getMessage() . "\n" . $e->getTraceAsString());
    }
    if($this->debug) {
      Kohana_Log::add("information", "[XMP Module] Finished extracting tags using WLPG.\n");
    }

  }

  protected function get_xpath_base(){
    if($this->debug) {
      Kohana_Log::add("information", "[XMP Module] get_xpath_base() (WLPG).\n");
    }

    return $this->xmpReader->getXPath()->query("//MPRI:Regions/rdf:Bag");
  }

  protected function check_node($node) {
    if($this->debug) {
      $node_text_xml = $node->ownerDocument->saveXml($node);
      Kohana_Log::add("information", "[XMP Module] Checking node name: {$node->nodeName}, value: {$node_text_xml}, and length: {$node->length}.\n");
    }

    $bPerson = $bRectangle = false;
    if( $node->nodeName == "MPReg:PersonDisplayName"  ||  $node->nodeName == "PersonDisplayName") {
      $person = $node->nodeValue;
      $this->people[] = $person;
      $bPerson = true;
      if($this->debug) {
        Kohana_Log::add("information", "[XMP Module] Found person: {$person}.\n");
      }
    } elseif ( $node->nodeName == "MPReg:Rectangle" || $node->nodeName == "Rectangle" ) {
        $bRectangle = true;
        $rectangle = $node->nodeValue;
        $this->rectangles[] = $rectangle;
        if($this->debug) {
          Kohana_Log::add("information", "[XMP Module] Found rectangle: {$rectangle}.\n");
        }
    }
    if( $bPerson || $bRectangle ) {
      return true;
    } else {
      return false;
    }
  }

  protected function check_children_for_faces($childNodes) {
    if($this->debug) {
      Kohana_Log::add("information", "[XMP Module] Checking child XML for face data.\n");
    }

    $bSuccess = false;
    if($childNodes->length > 0 ) {
      $bPerson = false;
      $bRectangle = false;
      for($i=0; $i < $childNodes->length; $i++) {
        if( $childNodes->item($i)->nodeName == "MPReg:PersonDisplayName" ) {
          $bPerson = true;
        } elseif ( $childNodes->item($i)->nodeName == "MPReg:Rectangle" ) {
          $bRectangle = true;
        }
      }
      if($bPerson && $bRectangle) {
        $bSuccess = true;
      }
    }
    if($this->debug) {
      Kohana_Log::add("information", "[XMP Module] Child XML face data found? {$bSuccess}.\n");
    }
    return $bSuccess;
  }

}
?>
