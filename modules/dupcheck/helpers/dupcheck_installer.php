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
class dupcheck_installer {
  static function install() {
    // Create table if it doesn't exist for md5sum storage
    $db = Database::instance();
    $db->query("CREATE TABLE IF NOT EXISTS {fullsize_md5sums} (
               `id` int(9) NOT NULL auto_increment,
               `item_id` int(9) NOT NULL,
               `itemmd5` varchar(128) NOT NULL,
               PRIMARY KEY (`id`),
               KEY(`item_id`, `id`))
               DEFAULT CHARSET=utf8;");

    // Set the module version number.
    module::set_version("dupcheck", 1);
  }
 
  static function activate() {
    dupcheck::check_index();
  }

  static function deactivate() {
    site_status::clear("md5_index_out_of_date");
  }
}
