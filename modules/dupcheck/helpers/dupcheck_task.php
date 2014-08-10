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

class Dupcheck_task_Core {
  static function available_tasks() {
    // Automatically delete extra md5sums whenever the maintance screen is loaded.
    db::build()
      ->delete("fullsize_md5sums")
      ->where("item_id", "NOT IN",
              db::build()->select("id")->from("items"))
      ->execute();

    // Display an option on the maintenance screen for scanning existing photos
    // for MD5SUMs (in case photos were uploaded before the module was active).
    list ($remaining, $total, $percent) = dupcheck::stats();
    return array(Task_Definition::factory()
                 ->callback("dupcheck_task::update_md5s")
                 ->name(t("Extract missing MD5SUMs"))
                 ->description($remaining
			       ? t2("1 photo needs to be scanned",
				    "%count (%percent%) of your photos need to be scanned",
				    $remaining, array("percent" => (100 - $percent)))
				      : t("All photo MD5sums are up-to-date"))
                 ->severity($remaining ? log::WARNING : log::SUCCESS));
  }

  static function update_md5s($task) {
    $completed = $task->get("completed", 0);
    $start = microtime(true);
    foreach (ORM::factory("item")
	     ->join("fullsize_md5sums", "items.id", "fullsize_md5sums.item_id", "left")
	     ->where("type", "=", "photo")
	     ->and_open()
	     ->where("fullsize_md5sums.item_id", "IS", null)
	     ->close()
	     ->find_all(100) as $item) {
      if(!isset($start)) {
	$start = microtime(true);
      }

      dupcheck::grabmd5($item);
      $completed++;

      if (microtime(true) - $start > .75) {
	break;
      }
    }

    list($remaining, $total, $percent) = dupcheck::stats();
    $task->set("completed", $completed);
    if ($remaining == 0 || !($remaining + $completed)) {
      $task->done = true;
      $task->state = "success";
      site_status::clear("md5_index_out_of_date");
      $task->percent_complete = 100;
    } else {
      $task->percent_complete = round(100 * $completed / ($remaining + $completed));
    }
    $task->status = t2("1 record updated, index is %percent% up-to-date",
		       "%count records updated, index is %percent% up-to-date",
			$completed, array("percent" => $percent));
  }
}
