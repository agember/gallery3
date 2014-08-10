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
class Admin_Menu_links_Controller extends Admin_Controller {
  public function index() {
    print $this->_get_view();
  }

  public function handler() {
    access::verify_csrf();

    $form = $this->_get_form();
    if ($form->validate()) {
      module::set_var(
        "menu_links", "title", $form->menu->menu_title->value);
      module::set_var(
        "menu_links", "url", $form->menu->menu_url->value);
      
      module::set_var (
	    "menu_links", "title2", $form->menu2->menu_title2->value);
	  module::set_var (
	    "menu_links", "hidden", $form->menu2->menu_hidden->value);

      module::set_var(
        "menu_links", "title3", $form->menu3->menu_title3->value);
      module::set_var(
        "menu_links", "url3", $form->menu3->menu_url3->value);

message::success(t("Your settings have been saved."));
url::redirect("admin/menu_links");
    }

    print $this->_get_view($form);
  }

  private function _get_view($form=null) {
    $v = new Admin_View("admin.html");
    $v->content = new View("admin_menu_links.html");
    $v->content->form = empty($form) ? $this->_get_form() : $form;
    return $v;
  }

  private function _get_form() {
    $form = new Forge("admin/menu_links/handler", "", "post", array("id" => "g-admin-form"));
    
    $group = $form->group("menu")->label(t("First link on menu"));
    $group->input("menu_title")->label(t('Enter the title.'))
		->value(module::get_var("menu_links", "title"));
    $group->input("menu_url")->label(t('Enter the URL of the link.'))
		->value(module::get_var("menu_links", "url"));
    
    $group = $form->group("menu2")->label(t("Gallery root album title"));
    $group->input("menu_title2")->label(t('Enter the title.'))
		->value(module::get_var("menu_links", "title2", "home"))
		->rules("required");
	$group->checkbox("menu_hidden")->label(t("Hide link"))
      ->checked(module::get_var("menu_links", "hidden", false) == 1);
    
    $group = $form->group("menu3")->label(t("Link after the Gallery root album."));
    $group->input("menu_title3")->label(t('Enter the title.'))->value(module::get_var("menu_links", "title3"));
    $group->input("menu_url3")->label(t('Enter the URL of the link.'))
			->value(module::get_var("menu_links", "url3"));
    
    $form->submit("submit")->value(t("Save"));

    return $form;
  }
}