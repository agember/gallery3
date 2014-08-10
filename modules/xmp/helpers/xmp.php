<?php defined("SYSPATH") or die("No direct script access.");
/**
* Gallery - a web based photo album viewer and editor
* Copyright (C) 2000-2010 Bharat Mediratta
*
* This program is free software; you can redistribute it and/or modify
* it under the terms of the GNU General Public License as published by
* the Free Software Foundation; either version 2 of the License, or (at
* your option) any later version.
*
* This program is distributed in the hope that it will be useful, but
* WITHOUT ANY WARRANTY; without even the implied warranty of
* MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU
* General Public License for more details.
*
* You should have received a copy of the GNU General Public License
* along with this program; if not, write to the Free Software
* Foundation, Inc., 51 Franklin Street - Fifth Floor, Boston, MA 02110-1301, USA.
*/

/**
* This is the API for handling xmp data
*/
class xmp_Core {

  static function extract($item, $force_debug) {
    $debug = module::get_var("xmp","logDebugInfo",false);
    $debug = $debug || $force_debug;

    $wlpg_tags = 0;
    $picasa_tags = 0;
    
    if($debug) {
      Kohana_Log::add("information", "[XMP Module] Extracting XMP data from an item: {$item->title} at {$item->file_path()}.\n");
    }

    require_once( MODPATH . "xmp/lib/Image/JpegXmpReader.php" );
    require_once( MODPATH . "xmp/lib/XMP/interpreter.php" );
    require_once( MODPATH . "xmp/lib/XMP/picasa_xmp.php" );
    require_once( MODPATH . "xmp/lib/XMP/wlpg_xmp.php" );

    if($debug) {
      Kohana_Log::add("information", "[XMP Module] Finished loading necessary files.\n");
    }

    try {
      $xmpReader = new Image_JpegXmpReader( $item->file_path() );
      $xmpData = $xmpReader->readXmp();
    } catch (Exception $e) {
       Kohana_Log::add("error", "[XMP Module] Error reading XML from {$item->title} at {$item->file_path()}.\n" .
                      $e->getMessage() . "\n" . $e->getTraceAsString());
    }
    if( $xmpData === false) {
      message::warning("[XMP Module] Warning, couldn't read any XMP data from \"{$item->title}\". Please erase all current metadata, or ensure there is some, and try again.");
      Kohana_Log::add("warning", "[XMP Module] Warning, couldn't read any XMP data from \"{$item->title}\". Please erase all current metadata, or ensure there is some, and try again." );
    } else {		//end of if XMP readable
      try {
        if($debug) {
          Kohana_Log::add("information", "[XMP Module] Extracting XMP data from using the WLPG interpreter.\n");
        }

        if(module::get_var("xmp","useWLPG",true)) {
          $wlpg = new wlpg_xmp($item, $xmpReader, $force_debug);
          $wlpg->extract();
          $wlpg_tags = $wlpg->get_tags_count();
          unset($wlpg);
        }
      } catch (Exception $e) {
        Kohana_Log::add("error", "[XMP Module] Error using the WLPG interpreter.\n" .
                      $e->getMessage() . "\n" . $e->getTraceAsString());
      }
      try {
        if($debug) {
          Kohana_Log::add("information", "[XMP Module] Extracting XMP data from using the Picasa interpreter.\n");
        }

        if(module::get_var("xmp","usePicasa",true)) {
          $picasa = new picasa_xmp($item, $xmpReader, $force_debug);
          $picasa->extract();
          $picasa_tags = $picasa->get_tags_count();
          unset($picasa);
        }
      } catch (Exception $e) {
        Kohana_Log::add("error", "[XMP Module] Error using the Picasa interpreter.\n" .
                      $e->getMessage() . "\n" . $e->getTraceAsString());
      }
    }

    $total_tags = $picasa_tags + $wlpg_tags;

    if($debug) {
      Kohana_Log::add("information", "[XMP Module] TOTAL TAGS ADDED: {$wlpg_tags} vs picasa {$picasa_tags}, total: {$total_tags}\n");
    }

    return $total_tags;
  }
}
?>
