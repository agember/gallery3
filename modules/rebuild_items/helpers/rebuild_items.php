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
class rebuild_items_Core {
	static function get_tags($album) {
	    foreach (db::build()
	        ->select("id", "name")
	        ->from("items")
	        ->where("parent_id", "=", $album)
			->where("type", "=", "photo")
	        ->execute() as $row) {
		    message::info(t('Found image: %name',
			array("name" => $row->name)));
		    $item = ORM::factory("item", $row->id);
		   rebuild_item::get_tags($item);
		}
	    return;
	}
}