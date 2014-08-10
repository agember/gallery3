<?php defined("SYSPATH") or die("No direct script access.");
/**
 * Gallery - a web based photo album viewer and editor
 * Copyright (C) 2000-2009 Bharat Mediratta
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
class dupcheck_Controller extends Controller {

  public function dupes() {
   // Figure out how many items to display on each page.
   $page_size = module::get_var("gallery", "page_size", 9);

   // Figure out which page # the visitor is on and
   //	don't allow the visitor to go below page 1.
   $page = Input::instance()->get("page", 1);
    if ($page < 1) {
      url::redirect("dupcheck/dupes");
    }

    // First item to display.
    $offset = ($page - 1) * $page_size;

    // Determine the total number of items,
    //	for page numbering purposes.
    $count = db::build()
	      ->select("itemmd5")
	      ->select(array("C" => "count(\"*\")"))
	      ->from("fullsize_md5sums")
	      ->group_by("itemmd5")
	      ->having("C", ">", 1)
	      ->execute()->count();
//    $count = $count*2;

    // Figure out what the highest page number is.
    $max_pages = ceil($count / $page_size);

    // Don't let the visitor go past the last page.
    if ($max_pages && $page > $max_pages) {
      url::redirect("dupcheck/dupes?page=$max_pages");
    }

    // Figure out which items to display on this page.
      foreach( db::build()
	      ->select("item_id")
	      ->select(array("C" => "count(\"*\")"))
	      ->from("fullsize_md5sums")
	      ->group_by("itemmd5")
	      ->having("C", ">", 1)
	      ->execute()
	      as $row ) {
      $dupes[] = $row->item_id;
    }

    if(empty($dupes)){
      $count = 0;
      $dupes[0] = 9999999;
    }

    $items = ORM::factory("item")
      ->where("id", "IN", $dupes)
      ->order_by("created", "DESC")
      ->find_all($page_size, $offset);

    // Set up the previous and next page buttons.
    if ($page > 1) {
      $previous_page = $page - 1;
      $view->previous_page_link = url::site("dupcheck/dupes?page={$previous_page}");
    }
    if ($page < $max_pages) {
      $next_page = $page + 1;
      $view->next_page_link = url::site("dupcheck/dupes?page={$next_page}");
    }

    // Set up and display the actual page.
    $template = new Theme_View("page.html", "collection", "DuplicatePhotos");
    $template->page_title = t("Gallery :: Duplicate Photos");
    $template->set_global("page", $page);
    $template->set_global("page_size", $page_size);
    $template->set_global("max_pages", $max_pages);
    $template->set_global("children", $items);
    $template->set_global("children_count", $count);
    $template->content = new View("dupespage.html");
    if($dupes[0] == 9999999){
    $template->content->title = t("No Duplicate Photos Found");
    } else {
    $template->content->title = t("Duplicate Photos");
    }
    print $template;
  }

}
