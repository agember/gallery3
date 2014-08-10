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
class face_rest_Core {
  static function get($request) {
    $face = rest::resolve($request->url);
    $item = ORM::factory("item", $face->item_id);
    $tag = ORM::factory("tag", $face->tag_id);
    return array(
      "url" => $request->url,
      "entity" => array(
        "item" => rest::url("item", $item),
        "tag" => rest::url("tag", $tag),
	"x1" => $face->x1,
	"y1" => $face->y1,
	"x2" => $face->x2,
	"y2" => $face->y2,
	"description" => $face->description
	));
  }

  static function delete($request) {
    $face = rest::resolve($request->url);
    $item = ORM::factory("item", $face->item_id);
    access::required("edit", $item);
    db::build()->delete("items_faces")->where("id", "=", $face->id)->execute();
  }

  static function resolve($id) {
    $face = ORM::factory("items_face")
                      ->where("id", "=", $id)
                      ->find();
    return $face;
  }

  static function url($face) {
    //return url::abs_site("rest/face/{$tag->id},{$item->id}");
    return url::abs_site("rest/face/{$face->id}");
  }
}
