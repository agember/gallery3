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
 * Foundation, Inc., 51 Franklin Street - Fifth Floor, Boston, MA  02110-1301!
 */
class Dupcheck_Event_Core {
  static function item_created($item) {
      dupcheck::grabmd5($item);
  }

  static function item_deleted($item) {
    if($item->is_photo()){
      db::build()
        ->delete("fullsize_md5sums")
        ->where("item_id", "=", $item->id)
        ->execute();
    }
  }

  static function item_updated_data_file($item) {
    dupcheck::updatemd5($item);
  }

  static function admin_menu($menu, $theme) {
    $menu->get("content_menu")
      ->append(Menu::factory("link")
               ->id("dupcheck")
               ->label(t("Gallery Photo Duplicates"))
               ->url(url::site("dupcheck/dupes")));
  }

}
