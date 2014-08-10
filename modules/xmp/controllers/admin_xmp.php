<?php defined("SYSPATH") or die("No direct script access.");
/**
* Gallery - a web based photo album viewer and editor
* Copyright (C) 2000-2010 Bharat Mediratta
* Module XMP - written by Tomek Kott
*
* This program is free software; you can redistribute it and/or modify
* it under the terms of the GNU General Public License as published by
* the Free Software Foundation; either version 2 of the License, or (at
* your option) any later version.
*
* This program is distributed in the hope that it will be useful, but
* WITHOUT ANY WARRANTY; without even the implied warranty of
* MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU
* General Public License for more details.
*
* You should have received a copy of the GNU General Public License
* along with this program; if not, write to the Free Software
* Foundation, Inc., 51 Franklin Street - Fifth Floor, Boston, MA 02110-1301, USA.
*/

class Admin_xmp_Controller extends Admin_Controller {
  public function index() {
    // Generate a new admin page.
    $view = new Admin_View("admin.html");
    $view->page_title = t("XMP Settings");
    $view->content = new View("admin_xmp.html");
    $view->content->xmp_form = $this->_get_admin_form();
    print $view;
  }

  public function saveprefs() {
    // Save user preferences to the database.

    // Prevent Cross Site Request Forgery
    access::verify_csrf();

    // Make sure the user filled out the form properly.
    $form = $this->_get_admin_form();
    if ($form->validate()) {
      // Save settings to Gallery's database.
      module::set_var("xmp", "extractTitle", $form->Extract->extractTitle->checked, false);
      module::set_var("xmp", "extractDescription", $form->Extract->extractDescription->checked, false);
      module::set_var("xmp", "extractTags", $form->Extract->extractTags->checked, false);
      module::set_var("xmp", "extractFaces", $form->Extract->extractFaces->checked, false);
      module::set_var("xmp", "extractRatings", $form->Extract->extractRatings->checked, false);

      module::set_var("xmp", "captionAsTitle", $form->Title->captionAsTitle->checked, false);

      module::set_var("xmp", "useWLPG", $form->Method->useWLPG->checked, true);
      module::set_var("xmp", "usePicasa", $form->Method->usePicasa->checked, true);

      module::set_var("xmp", "logDebugInfo", $form->Advanced->logDebugInfo->checked, false);
      module::set_var("xmp", "lastItemScanned", $form->Advanced->lastItemScanned->value);

      // Display a success message and redirect back to the TagsMap admin page.
      message::success(t("Your settings have been saved."));
      url::redirect("admin/xmp");
    }

    // Else show the page with errors
    $view = new Admin_View("admin.html");
    $view->content = new View("admin_xmp.html");
    $view->content->xmp_form = $form;
    print $view;
  }

  private function _get_admin_form() {
    // Make a new Form.
    $form = new Forge("admin/xmp/saveprefs", "", "post",
                      array("id" => "g-admin-form"));

    // Create group for extraction settings
    $xmp_extract_group = $form->group("Extract")
                             ->label(t("Extraction Settings"));

    $xmp_extract_group->checkbox("extractTitle")->label(t("Disable Title extraction"))
            ->checked(module::get_var("xmp","extractTitle",false));

    $xmp_extract_group->checkbox("extractDescription")->label(t("Disable Description extraction"))
            ->checked(module::get_var("xmp","extractDescription",false));

    $xmp_extract_group->checkbox("extractTags")->label(t("Disable Tags extraction"))
            ->checked(module::get_var("xmp","extractTags",false));

    $xmp_extract_group->checkbox("extractFaces")->label(t("Disable Faces extraction"))
            ->checked(module::get_var("xmp","extractFaces",false));

    $xmp_extract_group->checkbox("extractRatings")->label(t("Disable Ratings extraction"))
            ->checked(module::get_var("xmp","extractRatings",false));

    //Group for "Title Extraction"
    $xmp_title_group = $form->group("Title")
                             ->label(t("Caption as Title"));

    $xmp_title_group->checkBox("captionAsTitle")->label(t("Use the caption as the title"))
             ->checked(module::get_var("xmp","captionAsTitle",false));

    //Group for "Method Settings"
    $xmp_method_group = $form->group("Method")
                             ->label(t("Method Settings"));

    $xmp_method_group->checkbox("useWLPG")->label(t("Attempt to extract data using Windows Live Photo Gallery methods."))
            ->checked(module::get_var("xmp","useWLPG",true));

    $xmp_method_group->checkbox("usePicasa")->label(t("Attempt to extract data using Picasa methods."))
            ->checked(module::get_var("xmp","usePicasa",true));

    //Group for "Advanced Settings"
    $xmp_adv_group = $form->group("Advanced")
                             ->label(t("Advanced Settings"));

    $xmp_adv_group->checkbox("logDebugInfo")->label(t("Log debugging information"))
            ->checked(module::get_var("xmp","logDebugInfo",false));

    $xmp_adv_group->input("lastItemScanned")->label(t("Reset the last item scanned"))
            ->value(module::get_var("xmp","lastItemScanned","0"))
            ->rules("valid_numeric");

    // Add a save button to the form.
    $form->submit("SaveSettings")->value(t("Save"));

    // Return the newly generated form.
    return $form;
  }
}

?>
