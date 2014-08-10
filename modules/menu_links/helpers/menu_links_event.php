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
class menu_links_event_Core {
  static function admin_menu($menu, $theme) {
    $menu->get("settings_menu")
      ->append(Menu::factory("link")
            ->id("menu_links")
            ->label(t("Menu links"))
            ->url(url::site("admin/menu_links")));
  }
 static function site_menu($menu, $theme) {
	if (module::get_var("menu_links", "title") != null) {
		$menu->add_before("home", Menu::factory("link")
    		->id("root")
			->label(module::get_var("menu_links", "title"))
			->url(module::get_var("menu_links", "url")));
	}
	$menu->append(Menu::factory("link")
        ->id("home")
        ->css_id("g-menu-link-remove")
        ->label(module::get_var("menu_links", "title2"))
    	->url(item::root()->url()));

	if (module::get_var("menu_links", "title3") != null) {
		$menu->add_after("home", Menu::factory("link")
			->id("after_root")
			->label(module::get_var("menu_links", "title3"))
			->url(module::get_var("menu_links", "url3")));
	}
 }
}