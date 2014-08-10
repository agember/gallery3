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
class Admin_Rebuild_item_Controller extends Admin_Controller {
  public function index() {
   $album_id = Input::instance()->get("form_id");
    print $this->_get_view($album_id);
  }

  public function handler() {
   $form_id = Input::instance()->get("form_id");
    access::verify_csrf();
	$form = $this->_get_form();
	if ($form->validate()) {
		$build_iptc		= $form->rebuild->build_iptc->value;
		$build_exif		= $form->rebuild->build_exif->value;
		$build_tags		= $form->rebuild->build_tags->value;
		$build_thumb    = $form->rebuild->build_thumbs->value;
		$build_resize	= $form->rebuild->build_resizes->value;
		$force_rebuild  = $form->rebuild->force_rebuild->value;
		if ($build_iptc) {
			db::build()
			  ->update("iptc_records")
			  ->set("dirty", 1)
              ->where("item_id", "=", $form_id)
              ->execute();
			site_status::warning(
        	t('Your IPTC index needs to be updated.  <a href="%url" class="g-dialog-link">Fix this now</a>',
          		array("url" => html::mark_clean(url::site("admin/maintenance/start/iptc_task::update_index?csrf=__CSRF__")))),
        			"iptc_index_out_of_date");
		}
		if ($build_exif) {
		Kohana_Log::add('debug', 'got toEXIF');
			db::build()
			  ->update("exif_records")
			  ->set("dirty", 1)
              ->where("item_id", "=", $form_id)
              ->execute();
			site_status::warning(
        	t('Your Exif index needs to be updated.  <a href="%url" class="g-dialog-link">Fix this now</a>',
          		array("url" => html::mark_clean(url::site("admin/maintenance/start/exif_task::update_index?csrf=__CSRF__")))),
        			"exif_index_out_of_date");
		}
		if ($build_tags) {
			$form_id = Input::instance()->get("form_id");
			$item = ORM::factory("item", $form_id);
			rebuild_item::get_tags($item);
		}
		if ($build_thumb) {
			db::build()
      		  ->update("items")
      		  ->set("thumb_dirty", 1)
      		  ->where("id", "=", $form_id)
      		  ->execute();
			site_status::warning(
			t('One or more of your photos are out of date. Fix this now on <a href="%url">the maintenance page</a>.',
          		array("url" => url::site("admin/maintenance/"))),
        			"graphics_dirty");	
		}
		if ($build_resize) {
			db::build()
              ->update("items")
              ->set ("resize_dirty", 1)
              ->where("id", "=", $form_id)
              ->execute();
			site_status::warning(
			t('One or more of your photos are out of date. Fix this now on <a href="%url">the maintenance page</a>.',
          		array("url" => url::site("admin/maintenance/"))),
        			"graphics_dirty");
		}
	  url::redirect("admin/rebuild_item?form_id=$form_id");
    }
    print $this->_get_view($form);
  }

  private function _get_view($form_id) {
    $v = new Admin_View("admin.html");
    $v->content = new View("admin_rebuild_item.html");
	$v->content->item = ORM::factory("item", $form_id);
    $v->content->form = empty($form) ? $this->_get_form($form_id) : $form;
    return $v;
  }

  private function _get_form() {
    $form_id = Input::instance()->get("form_id");
    $item = ORM::factory("item", $form_id);
    $form = new Forge("admin/rebuild_item/handler?form_id=$form_id", "", "post", array("id" => "g-admin-form"));
    $group = $form->group("rebuild")
		->label(t('Rebuild item'));
	$group->input("album_id")->label(t("The item with this name will be changed:"))
		->value($item->title)->class('rebuild_text')->disabled(true);
	$group->input("text")
		->value("Only one task in the list that is checked will be performed.")
		->class('g-info')
		->disabled(true);
	if (module::is_active("iptc")) {
    $group->checkbox("build_iptc")->label(t("Reset IPTC Info"))
        ->class('g-unique')->checked(false);
    }
	if (module::is_active("exif")) {
    $group->checkbox("build_exif")->label(t("Reset Exif Info.  Existing tags will not be lost."))
        ->class('g-unique')->checked(false);
    }
	if (module::is_active("tag")) {
    $group->checkbox("build_tags")->label(t("Update tags for this item"))
        ->class('g-unique')->checked(false);
    }
    $group->checkbox("build_thumbs")->label(t("Mark dirty the thumb for this item."))
        ->class('g-unique')->checked(false);
    $group->checkbox("build_resizes")->label(t("Mark dirty the resize for this item."))
        ->class('g-unique')->checked(false);
    $group->submit("submit")->value(t("Commit changes"));

    return $form;
  }
}