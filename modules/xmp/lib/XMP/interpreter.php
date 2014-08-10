<?php
/**
 * Description of interpreter
 *
 * @author tkott
 */
require_once( MODPATH . "xmp/lib/Image/JpegXmpReader.php" );


abstract class interpreter {
  /**
   * EXIF schema namespace
   */
  const XMP_NS_EXIF = "http://ns.adobe.com/exif/1.0/";

  /**
   * XAP schema namespace
   *
   * Used for the extraction of ratings
   */
  const XMP_NS_XAP = "http://ns.adobe.com/xap/1.0/";

  protected $item;
  protected $xmpReader;
  protected $xml;
  protected $tags;
  protected $rating;
  protected $rectangles;
  protected $people;
  protected $tags_count;
  protected $debug;

  function __construct($item,$xmpReader,$force_debug) {
    $this->item = $item;
    $this->xmpReader = $xmpReader;
    $this->debug = module::get_var("xmp","logDebugInfo",false) || $force_debug;
    $this->xml = $this->xmpReader->readXmp();
    $this->tags = array();
    $this->rectangles = array();
    $this->people = array();
    $this->tags_count = 0;

    if($this->debug) {
      $this_class = get_class($this);
      Kohana_Log::add("information", "[XMP Module] Created class object {$this_class}.\n");
    }
  }

  public function get_tags_count() {return $this->tags_count;}

  abstract protected function check_node($node);
  abstract protected function check_children_for_faces($childNodes);
  abstract protected function get_xpath_base(); // returns region records

  public function extract() {
    if($this->debug) {
      Kohana_Log::add("information", "[XMP Module] extract() called!.\n");
    }
    try {
      if( !module::get_var("xmp","extractTitle",false) ) {
        $this->extract_title();
      }

      if( !module::get_var("xmp","extractDescription",false) ) {
        $this->extract_description();
      }

      if( module::is_active("tag") ) {
        if( !module::get_var("xmp","extractTags",false) ) {
          $this->extract_tags();

          if( !module::get_var("xmp","extractRatings",false) ) {
            $this->extract_rating();
            if($this->rating) {
              $this->tags[$this->rating] = 0;
            }
          }

          $this->add_extracted_tags_to_item();
        }

        // Extract tags from tagfaces/photoannotation...
        if( !module::get_var("xmp","extractFaces",false) ) {
          if ( module::is_active("photoannotation") && method_exists('photoannotation','saveface') ) {
            $this->extract_faces();
          }
        }
      }	//end of if is_active("tag")

      $this->item->save();
    } catch (Exception $e) {
      Kohana_Log::add("error", "[XMP Module] Error calling extract().\n" .
                      $e->getMessage() . "\n" . $e->getTraceAsString());
    }
    return $this->item;
  }

  protected function extract_title() {
    if($this->debug) {
      Kohana_Log::add("information", "[XMP Module] Extracting the title.\n");
    }
    try {
      if( module::get_var("xmp","captionAsTitle",false) ) {
        $title = $this->xmpReader->getDescription();
        if($this->debug) {
          Kohana_Log::add("information", "[XMP Module] Using the caption as the title.\n");
        }
      } else {
        $title = $this->xmpReader->getTitle();
      }
      if ($title && $this->item->title != $title) {//check if the title has changed!
        if($this->debug) {
          Kohana_Log::add("information", "[XMP Module] New title found. Old title: {$this->item->title}; New title: {$title}.\n");
        }
        $this->item->title = $title;
        $this->item->slug = preg_replace("/[^A-Za-z0-9-_]+/", "-", $title); //change the slug to be a friendly URL
        $base_slug = $this->item->slug;
        while (ORM::factory("item")
               ->where("parent_id", "=", $this->item->parent_id)
               ->and_open()
               ->where("slug", "=", $this->item->slug)
               ->close()
               ->find()->id) {
          $rand = rand();
          $this->item->slug = "$base_slug-$rand";
        }
      }
    } catch (Exception $e) {
      Kohana_Log::add("error", "[XMP Module] Error adding title.\n" .
                      $e->getMessage() . "\n" . $e->getTraceAsString());
    }
  }

  protected function extract_description() {
    if($this->debug) {
      Kohana_Log::add("information", "[XMP Module] Extracting the description.\n");
    }
    try {
      $description = $this->xmpReader->getDescription();
      if ($description) {
        $this->item->description = $description;
      } else {
        $caption = $this->xmpReader->getImplodedField("UserComment", self::XMP_NS_EXIF);
        if($caption) {
          if(strlen($caption) > 2040 ) { // the maximum field length is 2048, so just check for too long!
            $caption = substr($caption, 0, 2040);
          }
          $this->item->description = $caption;
        }
      }
      if($this->debug) {
        Kohana_Log::add("information", "[XMP Module] Description found: {$description}.\n");
      }
    } catch (Exception $e) {
      Kohana_Log::add("error", "[XMP Module] Error adding the description.\n" .
                      $e->getMessage() . "\n" . $e->getTraceAsString());
    }
  }

  protected function extract_rating() {
    if($this->debug) {
      Kohana_Log::add("information", "[XMP Module] Extracting the rating (stars) using the generic method.\n");
    }
    try {
      //Try using the Adobe XMP tag for rating first
      $this->rating = $this->xmpReader->getImplodedField("Rating", self::XMP_NS_XAP);

      $this->interpret_rating();

    } catch (Exception $e) {
      Kohana_Log::add("error", "[XMP Module] Error adding the rating.\n" .
                      $e->getMessage() . "\n" . $e->getTraceAsString());
    }
  }

  protected function interpret_rating() {
    if ($this->rating) {
      if ($this->rating == 1) {
         $this->rating = "1star";
      } elseif ($this->rating == 2 or $this->rating == 25) {
         $this->rating = "2stars";
      } elseif ($this->rating == 3 or $this->rating == 50) {
         $this->rating = "3stars";
      } elseif ($this->rating == 4 or $this->rating == 75) {
         $this->rating = "4stars";
      } elseif ($this->rating == 5 or $this->rating == 99) {
         $this->rating = "5stars";
      } else {
         $this->rating = "UnknownRating";
      }
      if($this->debug) {
        Kohana_Log::add("information", "[XMP Module] Found a rating: {$this->rating}.\n");
      }
    }
  }

  protected function extract_tags() {
    if($this->debug) {
      Kohana_Log::add("information", "[XMP Module] Extracting tags using generic interpreter.\n");
    }
    $new_tags = $this->xmpReader->getSubjects();
    foreach( $new_tags as $unsplitTag ) {
      $splitTags =  explode('/', $unsplitTag);
      foreach ($splitTags as $tag) {
        $this->tags[$tag] = 0;
        if($this->debug) {
          Kohana_Log::add("information", "[XMP Module] Tag read using {xmpReader->getSubjects()}: {$tag}.\n");
        }
      }
    }
    if($this->debug) {
      Kohana_Log::add("information", "[XMP Module] Finished extracting tags using generic interpreter.\n");
    }
  }

  protected function add_extracted_tags_to_item() {
    if($this->debug) {
      Kohana_Log::add("information", "[XMP Module] Adding new tags to item after check for current tags.\n");
    }
    try{
      $c = 0;

      if( !empty($this->tags) ) {
        // Check for any already existing tags, and stomp over the tags array with a non-zero reference to prevent adding duplicates
        $currentTags = tag::item_tags($this->item);
        foreach ($currentTags as $tagObject) {
          if($this->debug) {
            Kohana_Log::add("information", "[XMP Module] Current tag found. Tag: {$tagObject->name}, ID: {$tagObject->id}.\n");
          }
          $this->tags[$tagObject->name] = $tagObject->id;
        }

        foreach ($this->tags as $tag=>$value) {
          if( !$value ) {
            $addedTag = tag::add($this->item, $tag);
            $this->tags[$tag] = $addedTag->id;
            if($this->debug) {
              Kohana_Log::add("information", "[XMP Module] Tag added to item. Tag: {$tag}, ID: {$addedTag->id}.\n");
            }
            $c++;
          }
        } //end of foreach tag
      } //end of if there are tags

      $this->tags_count += $c;
    } catch (Exception $e) {
      Kohana_Log::add("error", "[XMP Module] Error adding the tags.\n" .
                      $e->getMessage() . "\n" . $e->getTraceAsString());
    }
  }

  protected function extract_faces() {
    if($this->debug) {
      Kohana_Log::add("information", "[XMP Module] Extracting faces (generic).\n");
    }
    try {
      $rec = $this->get_xpath_base();
      if( $rec->length ) {
        if($this->debug) {
          Kohana_Log::add("information", "[XMP Module] {$rec->length} Regions found. Attempting to recursively extract faces.\n");
        }
        $bSuccess = $this->recursive_extract($rec,false);
      }
      if( $bSuccess && ( count($this->rectangles) == count($this->people) ) ){
        if($this->debug) {
          Kohana_Log::add("information", "[XMP Module] Found an equal amount of rectangles and faces in the metadata.\n");
        }
        $annotation_id = array();
        foreach($this->people as $person) {
          if( !array_key_exists($person, $this->tags) ) {
            //if the person doesn't exist as a tag, then add them as a tag first
            //add the tags here so I can use them when saving face annotations as a face.
            $addedTag = tag::add($this->item, $person);
            $this->tags_count += 1;
            $this->tags[$person] = $addedTag->id;
            $annotation_id[$person] = "";
            if($this->debug) {
              Kohana_Log::add("information", "[XMP Module] Found a new person tag: {$person}; new tag id: {$addedTag->id}.\n");
            }
          } else {
            //if the person already exists as a tag, then see if the
            //annotation is also present. If it is, update the annotation
            $existingFace = ORM::factory("items_face")
               ->where("tag_id", "=", $this->tags[$person])
               ->and_where("item_id", "=", $this->item->id)
               ->find_all(1);

            if($this->debug) {
              Kohana_Log::add("information", "[XMP Module] Found an old person tag: {$person}. Exisitng tag ID: {$this->tags[$person]}. Existing face id: {$existingFace->id}\n");
            }

            if( count($existingFace) > 0 ) {
              $annotation_id[$person] = $existingFace->id;
            } else {
              $annotation_id[$person] = "";
            }
          }
        }

        $defDimensions = $this->scale_dimensions( module::get_var("gallery", "resize_size"), $this->item->height, $this->item->width );

        if($this->debug) {
          $implodedDims = implode(",", $defDimensions);
          Kohana_Log::add("information", "[XMP Module] Checking dimensions. Scaled Dimensions: {$implodedDims}.\n");
        }

        $height = $defDimensions[0];
        $width = $defDimensions[1];

        for($i = 0; $i < count($this->people); ++$i){
          $aRect = explode(",",$this->rectangles[$i]);
          //I need to get the default size for images on this installation.

          $x =  $aRect[0] * $width;
          $y =  $aRect[1] * $height;
          $dx = $aRect[2] * $width;
          $dy = $aRect[3] * $height;

          if( $dx < 0.05 || $dy < 0.05 ) { //the face square will be small
            $addX = 0.05 * $width;
            $addY = 0.05 * $height;
            $x1 = floor( $x - ( $addX / 2 ) );
            $x2 = floor( $x + ( $addX / 2 ) );
            $y1 = floor( $y - ( $addY / 2 ) );
            $y2 = floor( $y + ( $addY / 2 ) );
          } else {
            $x1 = floor( $x );
            $x2 = floor( $x + $dx );
            $y1 = floor( $y );
            $y2 = floor( $y + $dy );
          }

          if ( $x1 < 0 ) { $x1 = 0; }
          if ( $x2 > $width ) { $x2 = $width; }
          if ( $y1 < 0 ) { $y1 = 0; }
          if ( $y2 > $height ) { $y2 = $height; }

          if($this->debug) {
            Kohana_Log::add("information", "[XMP Module] Saving face information. Person: {$this->tags[$this->people[$i]]}" .
              ",Item ID: {$this->item->id}, Location: ($x1,$y1,$x2,$y2), Annotation ID: {$annotation_id[$this->people[$i]]}.\n");
          }

          if(module::is_active("photoannotation") && method_exists('photoannotation','saveface')) {
            photoannotation::saveface($this->tags[$this->people[$i]],$this->item->id,$x1,$y1,$x2,$y2,"",$annotation_id[$this->people[$i]]);
          }
        }//end of for faces

      }
    } catch (Exception $e) {
      Kohana_Log::add("error", "[XMP Module] Error extracting and adding faces!\n" .
                  $e->getMessage() . "\n" . $e->getTraceAsString());
    }

    return $item;
  }

  protected function attributes_to_nodes(&$node) {
    if($node->hasAttributes()) {
      $attributes = $node->attributes;
      foreach ($attributes as $attribute) {
        $element = new DOMElement($attribute->name, $attribute->value);
        $node->appendChild($element);
        if($this->debug) {
          $node_text_xml = $node->ownerDocument->saveXml($element);
          Kohana_Log::add("information", "[XMP Module] Attribute moving to node: {$element->nodeName}, value: {$node_text_xml}");
        }
      }
    }
  }
  protected function recursive_extract($baseNode,$bFaces) {
    if($this->debug) {
      Kohana_Log::add("information", "[XMP Module] Recursively extracting face information.\n");
    }

    $bSuccess = false;

    if( $bFaces ) { //previous level told us we have children, so extract them!
      $bSuccess = true;
      if($this->debug) {
        Kohana_Log::add("information", "[XMP Module] Started checking for a face/rectangle pair.\n");
      }
      for($i=0; $i < $baseNode->length; $i++) { //for each item in the DOMNodeList ($baseNode)
        $node = $baseNode->item($i);
        $this->check_node($node);
      }
      if($this->debug) {
        Kohana_Log::add("information", "[XMP Module] Finished checking for a face/rectangle pair.\n");
      }
    } else {
      //baseNode is a DOMNodeList, so whatever I do, I need to check that it has a length
      if ( $baseNode->length > 0 ) {// if there is a length, I need to go through my usual checks
        for($i=0; $i < $baseNode->length; $i++) { //for each item in the DOMNodeList ($baseNode)
          $node = $baseNode->item($i);
          if( !($node instanceof DOMElement) ) {
            continue;
          }

          $this->attributes_to_nodes($node);
          
          if($this->debug) {
            $node_text_xml = $node->ownerDocument->saveXml($node);
            Kohana_Log::add("information", "[XMP Module] Checking node name: {$node->nodeName}, value: {$node_text_xml}");
          }
          //$bSuccess += $this->check_node($node);

          //check the children
          if ($node->hasChildNodes() ) {
            $childNodes = $node->childNodes;
            $bFaces = $this->check_children_for_faces($childNodes);
            if( $bFaces ) {
              $bTmpSuccess = $this->recursive_extract($childNodes,true);
            } else {
              $bTmpSuccess = $this->recursive_extract($childNodes,false);
            }
            $bSuccess += $bTmpSuccess;
          }
        } //on of for loop (over each item in DOMNodeList)
      }
    }

    return $bSuccess;
  }

  protected function scale_dimensions($resize, $itemHeight, $itemWidth) {
    // What are the possibilities for size of an image w.r.t. the default size?
    // 1. Both height and width are smaller than $defaultSize
    // 2. Only height is smaller
    // 3. Only width is smaller
    // 4. Both height and width are bigger than $defaultSize
    //
    if($this->debug) {
      Kohana_Log::add("information", "[XMP Module] Scaling dimensions from ({$itemHeight},{$itemWidth}) to a max box size of {$resize}.\n");
    }

    $output[0] = $itemHeight; //Height
    $output[1] = $itemWidth;  //Width

    if( $itemHeight > $resize && $itemWidth < $resize ) {
      $ratio = $itemHeight / $resize;
      $output[0] = $resize; // Height is the maximum dimension, so set it to resize
      $output[1] = (int) ($itemWidth / $ratio); // since $ratio > 1, divide to get the right value
    }
    elseif( $itemHeight < $resize && $itemWidth > $resize ) {
      $ratio = $itemWidth / $resize;
      $output[1] = $resize; // $resize is the maximum dimension, so set it to resize
      $output[0] = (int) ($itemHeight / $ratio); // since $ratio > 1, divide to get the right value
    }
    elseif( $itemHeight >= $resize && $itemWidth >= $resize ) {
      if($itemHeight >= $itemWidth){
        $ratio = $itemHeight / $resize;
        $output[0] = $resize; // Height is the maximum dimension, so set it to resize
        $output[1] = (int) ($itemWidth / $ratio); // since $ratio > 1, divide to get the right value
      }
      else {
        $ratio = $itemWidth / $resize;
        $output[1] = $resize; // $resize is the maximum dimension, so set it to resize
        $output[0] = (int) ($itemHeight / $ratio); // since $ratio > 1, divide to get the right value
      }
    }

    return $output;

  }

}
?>
