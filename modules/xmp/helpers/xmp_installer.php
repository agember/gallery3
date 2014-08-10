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
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the GNU
 * General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin Street - Fifth Floor, Boston, MA  02110-1301, USA.
 */
class xmp_installer {
  static function can_activate() {
    $messages = array();
    if( ! self::file_exists_ip("System.php")){
      $messages["error"][] =
        t("The XMP module requires PEAR to be installed and in a location that is in the include path. Unfortunately, this does not seem to be the case. include_path: " . get_include_path());
    }
    if( !self::file_exists_ip(MODPATH . "xmp/lib/Image/JpegXmpReader.php") ){
      $messages["error"][] = 
        t("The XMP module could not find the required additional library JpegXmpReader in the 'lib/Image' folder of the module.");
    }

    return $messages;
  }

  static function activate() {
    module::set_var("xmp","lastItemScanned","0");
    module::set_var("xmp","logDebugInfo",false);
  }

  static function deactivate() {
    site_status::clear("xmp_needs_tag");
  }

  static function file_exists_ip($filename) {
    $debug = module::get_var("xmp","logDebugInfo",false);
    if($debug) {
      Kohana_Log::add("information", "[XMP Module] Trying to find file: {$filename}.\n");
    }
    
    //First check the simplest solution!
    if( file_exists($filename) ) {return true; }

    if(function_exists("get_include_path")) {
      $include_path = get_include_path();
    } elseif(false !== ($ip = ini_get("include_path"))) {
      $include_path = $ip;
    } else {return false;}

    if(false !== strpos($include_path, PATH_SEPARATOR)) {
      if(false !== ($temp = explode(PATH_SEPARATOR, $include_path)) && count($temp) > 0) {
        for($n = 0; $n < count($temp); $n++) {
          if(false !== @file_exists($temp[$n] . DIRECTORY_SEPARATOR .  $filename)) {
            return true;
          }
        }
        return false;
      } else {return false;}
    } elseif(!empty($include_path)) {
      if(false !== @file_exists($include_path . DIRECTORY_SEPARATOR .  $filename)) {
        return true;
      } else {return false;}
    } else {return false;}
  }

}
