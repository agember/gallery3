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
class Admin_Rebuild_items_Controller extends Admin_Controller {
  public function index() {
   $album_id = Input::instance()->get("album_id");
    print $this->_get_view($album_id);
  }

  public function handler() {
   $album_id 	= Input::instance()->get("album_id");
   $thumb_size	= module::get_var("gallery", "thumb_size");
   $resize_size	= module::get_var("gallery", "resize_size");
    access::verify_csrf();
	$form = $this->_get_form();
	if ($form->validate()) {
		$build_iptc			= $form->rebuild->build_iptc->value;
    	$build_exif			= $form->rebuild->build_exif->value;
    	$build_thumbs   	= $form->rebuild->build_thumbs->value;
    	$build_resizes		= $form->rebuild->build_resizes->value;
		$build_big_thumbs  	= $form->rebuild->build_big_thumbs->value;
		$build_big_resizes  = $form->rebuild->build_big_resizes->value;
		$build_tags         = $form->rebuild->build_tags->value;
    if ($build_iptc):
        db::build()
          ->update("iptc_records")
          ->set("dirty", 1)
          ->where("item_id", "in", db::build()->select("id")->from("items")->where("parent_id", "=", $album_id))
          ->execute();
		site_status::warning(
        	t('Your IPTC index needs to be updated.  <a href="%url" class="g-dialog-link">Fix this now</a>',
          		array("url" => html::mark_clean(url::site("admin/maintenance/start/iptc_task::update_index?csrf=__CSRF__")))),
        			"iptc_index_out_of_date");

	elseif ($build_tags):
        rebuild_items::get_tags($album_id);

    elseif ($build_exif):
        db::build()
          ->update("exif_records")
          ->set("dirty", 1)
          ->where("item_id", "in", db::build()->select("id")->from("items")->where("parent_id", "=", $album_id))
          ->execute();
		site_status::warning(
        	t('Your Exif index needs to be updated.  <a href="%url" class="g-dialog-link">Fix this now</a>',
          		array("url" => html::mark_clean(url::site("admin/maintenance/start/exif_task::update_index?csrf=__CSRF__")))),
        			"exif_index_out_of_date");

    elseif ($build_thumbs):
    	db::build()
      		->update("items")
      		->set("thumb_dirty", 1)
      		->where("parent_id", "=", $album_id)
      		->execute();
      site_status::warning(
      	t('One or more of your photos are out of date. Fix this now on <a href="%url">the maintenance page</a>.',
          		array("url" => url::site("admin/maintenance/"))),
        			"graphics_dirty");	

    elseif ($build_resizes):
        db::build()
          ->update("items")
          ->set ("resize_dirty", 1)
          ->where("parent_id", "=", $album_id)
          ->execute();
      site_status::warning(
      	t('One or more of your photos are out of date. Fix this now on <a href="%url">the maintenance page</a>.',
          		array("url" => url::site("admin/maintenance/"))),
        			"graphics_dirty");
    elseif ($build_big_thumbs):
    	db::build()
      		->update("items")
      		->set("thumb_dirty", 1)
			->where("parent_id", "=", $album_id)
      		->where("thumb_height", ">", $thumb_size)
      		->execute();
      site_status::warning(
      	t('One or more of your photos are out of date. Fix this now on <a href="%url">the maintenance page</a>.',
          		array("url" => url::site("admin/maintenance/"))),
        			"graphics_dirty");	

    elseif ($build_big_resizes):
        db::build()
          ->update("items")
          ->set ("resize_dirty", 1)
		  ->where("parent_id", "=", $album_id)
		  ->where("resize_height", ">", $resize_size)
          ->execute();
      site_status::warning(
      	t('One or more of your photos are out of date. Fix this now on <a href="%url">the maintenance page</a>.',
          		array("url" => url::site("admin/maintenance/"))),
        			"graphics_dirty");		
    endif;
	  url::redirect("admin/rebuild_items?album_id=$album_id");
    }
    print $this->_get_view($form);
  }

  private function _get_view($album_id) {
    $v = new Admin_View("admin.html");
    $v->content = new View("admin_rebuild_items.html");
	$v->content->item = ORM::factory("item", $album_id);
    $v->content->form = empty($form) ? $this->_get_form($album_id) : $form;
    return $v;
  }

  private function _get_form() {
    $album_id = Input::instance()->get("album_id");
    $thumb_size	= module::get_var("gallery", "thumb_size");
    $resize_size	= module::get_var("gallery", "resize_size");
	$big_thumb_count = db::build()
      ->select("id")->from("items")
      ->where("thumb_height", ">", $thumb_size)
	  ->where("parent_id", "=", $album_id)
      ->count_records();
	$big_resize_count = db::build()
      ->select("id")->from("items")
      ->where("resize_height", ">", $resize_size)
	  ->where("parent_id", "=", $album_id)
      ->count_records();

    $album = ORM::factory("item", $album_id);
    $form = new Forge("admin/rebuild_items/handler?album_id=$album_id", "", "post", array("id" => "g-admin-form"));
    $group = $form->group("find_them")->label(t("Items in this album that are oversized"));
    $group->input("big_thumb_count")->label(t('Quantiy of thumbs in this album that are larger than the default setting.'))
		->value($big_thumb_count)
		->class('rebuild_number')
		->disabled(true);
	$group->input("big_resize_count")->label(t('Quantiy of resizes in this album that are larger than the default setting.'))
		->value($big_resize_count)
		->class('rebuild_number')
		->disabled(true);
		
    $group = $form->group("rebuild")
		->label(t('Rebuild items'));
	$group->input("album_id")->label(t("Items in this album will be changed:"))
	    ->class('rebuild_text')
		->value($album->title)->disabled(true);
	$group->input("text")
		->value("Only one task in the list that is checked will be performed.")
		->class('g-info')
		->disabled(true);
	if (module::is_active("iptc")) {
    $group->checkbox("build_iptc")->label(t("Reset IPTC Info"))
        ->class('g-unique')->checked(false);
    }
	if (module::is_active("exif")) {
    $group->checkbox("build_exif")->label(t("Reset Exif Info"))
        ->class('g-unique')->checked(false);
    }
	if ($big_thumb_count > 0) {
    $group->checkbox("build_big_thumbs")->label(t("Mark dirty the thumbs in this album that are oversize."))
        ->class('g-unique')->checked(false);
	} else {
	$group->checkbox("build_big_thumbs")->label(t("According to a database query, there is no thumbs in this album that are oversize."))
        ->class('g-unique')->checked(false)
		->disabled(true);
	}
	if ($big_resize_count > 0) {
    $group->checkbox("build_big_resizes")->label(t("Mark dirty the resizes in this album that are oversize."))
        ->class('g-unique')->checked(false);
	} else {
	$group->checkbox("build_big_resizes")->label(t("According to a database query, there is no resizes in this album that are oversize."))
        ->class('g-unique')->checked(false)
		->disabled(true);
	}
	if (module::is_active("tag")) {
	$group->checkbox("build_tags")->label(t("Update tags for all items"))
		->class('g-unique')->checked(false);
	}
	$group->checkbox("build_thumbs")->label(t("Mark dirty all the thumbs for this album."))
        ->class('g-unique')->checked(false);
    $group->checkbox("build_resizes")->label(t("Mark dirty all the resizes for this album."))
        ->class('g-unique')->checked(false);

    $group->submit("submit")->value(t("Commit changes"));

    return $form;
  }
}