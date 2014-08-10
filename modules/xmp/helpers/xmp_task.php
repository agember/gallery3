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
class xmp_task_Core {
  static function available_tasks() {
    $lastItemScanned = module::get_var("xmp","lastItemScanned","1");
    $photo_count = ORM::factory("item")
      ->where("type", "=", "photo")
      ->and_where("id", ">", "{$lastItemScanned}")
      ->count_all();

    return array(
         Task_Definition::factory()
           ->callback("xmp_task::extract_xmp")
           ->name(t("Extract XMP data"))
           ->description(t("Rescan {$photo_count} new photos and attempt to extract basic XMP without duplication. To rescan all photos, reset the last item count in the XMP settings."))
           ->severity(log::INFO)
        );
  }

  static function extract_xmp($task) {
    try {
      $lastItemScanned = module::get_var("xmp","lastItemScanned","1");
      if( !is_numeric($lastItemScanned) ) {
        $lastItemScanned = 0;
        message::error("During the last XMP scan, the id of the item to start scanning was not a number, so reset the last id to 0.");
        $task->log("During the last XMP scan, the id of the item to start scanning was not a number, so reset the last id to 0.");
      }

      $task->log("Starting the scan at item #{$lastItemScanned}");

      $photo_count = ORM::factory("item")->where("type", "=", "photo")->count_all();
      $start = microtime(true);
      $did = 0;	// Used to force a check of how many items we did this loop to return completed if we are done.  Really, need to get this cleaned up to not start at some lastItemScanned, and know how many we need to do like Exif extract.

      $completed = $task->get("completed", 0);
      $total_tags_added = $task->get("tags_added",0);

      foreach( ORM::factory("item")
               ->where("type", "=", "photo")
               ->and_where("id", ">", "{$lastItemScanned}")
               ->order_by("id", "asc")
               ->find_all(100) as $item) {
        // The query above can take a long time, so start the timer after its done
        // to give ourselves a little time to actually process rows. shouldn't scan id >...just get next one with table join (dirty flag?)
        if (!isset($start)) {
          $start = microtime(true);
        }

        //This should check if the file is there
        $item_exists = xmp_installer::file_exists_ip($item->file_path());
        if( $item_exists ) {
          $tags_added = xmp::extract($item,false);

          $total_tags_added += $tags_added;
          $task->set("tags_added",$total_tags_added);

          $task->log("Processed photo, (id {$item->id}) \"{$item->title}\", adding {$tags_added} tags");
          $task->status = "Photos scanned: {$completed} / {$photo_count} -- id {$item->id} \"{$item->title}\"";
          $lastItemScanned = $item->id;
        }

        $completed++;
        $did++;
        $task->percent_complete = $completed / $photo_count * 100;
        $task->set("completed", $completed);

        if (microtime(true) - $start > 10) {
          break;
        }
      }

      if (!$did) {		// Keep going until we get through them all...
        $total_tags_added = $task->get("tags_added");
        $task->done = true;
        $task->state = "success";
        $task->status = "Successfully scanned {$completed} photos and added {$total_tags_added} tags.";
        $task->percent_complete = 100;
      }

      module::set_var("xmp","lastItemScanned",$lastItemScanned);
    } catch (Exception $e) {
      $task->done = true;
      $task->state = "error";
      $task->status = $e->getMessage();
      $task->log((string)$e);
    }
  }
  
}
