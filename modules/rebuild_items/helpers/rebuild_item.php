<?php defined("SYSPATH") or die("No direct script access.");
/**
 * Gallery - a web based photo album viewer and editor
 * Copyright (C) 2000-2012 Bharat Mediratta
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

/**
 * This is the API for rebuilding data.
 */
class rebuild_item_Core {
	static function get_tags($photo) {
	  $path = $photo->file_path();
      $size = getimagesize($photo->file_path(), $info);
      if (is_array($info) && !empty($info["APP13"])) {
        $iptc = iptcparse($info["APP13"]);
        if (!empty($iptc["2#025"])) {
          foreach($iptc["2#025"] as $tag) {
            $tag = str_replace("\0",  "", $tag);
            foreach (explode(",", $tag) as $word) {
              $word = trim($word);
              $word = encoding::convert_to_utf8($word);
              $tags[$word] = 1;
            }
          }
        }
      }
    // @todo figure out how to read the keywords from xmp
    foreach(array_keys($tags) as $tag) {
      try {
        tag::add($photo, $tag);
		message::success(t("Added: $tag"));
      } catch (Exception $e) {
        Kohana_Log::add("error", "Error adding tag: $tag\n" .
                    $e->getMessage() . "\n" . $e->getTraceAsString());
      }
    }
	message::info(t('Back to: <a href="%url">%img</a>',
          array("img" => $photo->title,
          		"url" => $photo->url())));
  return;
  }
}
