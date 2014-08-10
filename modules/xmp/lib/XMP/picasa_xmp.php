<?php
/* 
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of picasa_interpreter
 *
 * @author tkott
 */
class picasa_xmp extends interpreter {

  protected function extract_title() {
    parent::extract_title();
  }
  protected function extract_description() {
    parent::extract_description();
  }

  protected function extract_rating() {
    parent::extract_rating();
  }

  protected function extract_tags() {
    parent::extract_tags();
  }

  protected function get_xpath_base(){
    if($this->debug) {
      Kohana_Log::add("information", "[XMP Module] get_xpath_base() (Picasa).\n");
    }

    //Returns a DOMNodeList
    return $this->xmpReader->getXPath()->query("//mwg-rs:RegionList/rdf:Bag");
  }

  protected function check_node($node) {
    if($this->debug) {
      $node_text_xml = $node->ownerDocument->saveXml($node);
      Kohana_Log::add("information", "[XMP Module] Checking node name: {$node->nodeName}, value: {$node_text_xml}, and length: {$node->length}.\n");
    }

    if( $node->nodeName == "mwg-rs:Name" ||  $node->nodeName == "Name") {
      $person = $node->nodeValue;
      $this->people[] = $person;
      $bPerson = true;
      if($this->debug) {
        Kohana_Log::add("information", "[XMP Module] Found person: {$person}.\n");
      }
    } elseif ( $node->nodeName == "mwg-rs:Area" || $node->nodeName == "Area" ) {
      $x = $y = $dx = $dy = 0;
      if($node->hasAttributes()) {
        $attributes = $node->attributes;
        foreach ($attributes as $attribute) {
          switch($attribute->name) {
            case "stArea:h":
            case "h":
              $dy = $attribute->value;
              break;
            case "stArea:w":
            case "w":
              $dx = $attribute->value;
              break;
            case "stArea:x":
            case "x":
              $x = $attribute->value;
              break;
            case "stArea:y":
            case "y":
              $y = $attribute->value;
              break;
          }
        }
      }
      if( $x && $y && $dx && $dy ) {
        $bRectangle = true;
        $x = $x - $dx/2;
        $y = $y - $dy/2;
        $rectangle = $x . "," . $y . "," . $dx . "," . $dy;
        $this->rectangles[] = $rectangle;
        if($this->debug) {
          Kohana_Log::add("information", "[XMP Module] Found rectangle: {$rectangle}.\n");
        }
      } // end of 'if all rectangle parameters set correctly'
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
        if( $childNodes->item($i)->nodeName == "mwg-rs:Name"
                || $childNodes->item($i)->nodeName == "Name") {
          $bPerson = true;
        } elseif ( $childNodes->item($i)->nodeName == "mwg-rs:Area"
                || $childNodes->item($i)->nodeName == "Area") {
          $bRectangle = true;
        }
      }
      if($bPerson && $bRectangle) {
        $bSuccess = true;
      }
    }
    if($this->debug) {
      $tf = ($bSuccess) ? 'true' : 'false';
      Kohana_Log::add("information", "[XMP Module] Child XML face data found? {$tf}.\n");
    }
    return $bSuccess;
  }

}
?>
