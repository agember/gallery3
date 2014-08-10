<?php defined("SYSPATH") or die("No direct script access.");
      if (locales::is_rtl()) {
        $rtl_support = "rtl: true,\n";
      } else {
        $rtl_support = "rtl: false,\n";
      }
      $carouselwidth = module::get_var("navcarousel", "carouselwidth", "600");
      if ($carouselwidth == 0) {
        $carouselwidth = "100%";
        $containerwidth = "";
      } else {
        $carouselwidth = $carouselwidth ."px";
        $containerwidth = ".jcarousel-skin-tango .jcarousel-container-horizontal {\n
                    width: ". $carouselwidth .";\n
                }\n";
      }
      $thumbsize = module::get_var("navcarousel", "thumbsize", "50");
      
      $showelements = module::get_var("navcarousel", "showelements", "7");
      $childcount = $theme->item->parent()->viewable()->children_count();
      $itemoffset = intval(floor($showelements / 2));
      if ($childcount <= $showelements) {
        $itemoffset = 1;
      } else {
        $itempos = $theme->item->parent()->get_position($theme->item);
        $itemoffset = $itempos - $itemoffset;
        if ($itemoffset < 1) {
          $itemoffset = 1;
        }
        if (($itemoffset + $showelements) > $childcount) {
          $itemoffset = $childcount - $showelements + 1;
        }
      }
      if (module::get_var("navcarousel", "noajax", false)) {
        $ajaxhandler = "";
      } else {
        $ajaxhandler = "itemLoadCallback: navcarousel_itemLoadCallback,\n";
      }
      if (module::get_var("navcarousel", "showondomready", false)) {
        $onwinload = "";
      } else {
        $onwinload = "});\n
                  $(window).load(function () {\n";
      }
      echo "\n<!-- Navcaoursel -->
                <style type=\"text/css\">\n
                ". $containerwidth ."
                .jcarousel-skin-tango .jcarousel-clip-horizontal {\n
                    width:  ". $carouselwidth .";\n
                    height: ". ($thumbsize + 25) ."px;\n
                }\n
                .jcarousel-skin-tango .jcarousel-item {\n
                    width: ". ($thumbsize + 25) ."px;\n
                    height: ". ($thumbsize + 25) ."px;\n
                }\n
                #navcarousel-loader {\n
                    height: ". ($thumbsize + 25) ."px;\n
                }\n
                .jcarousel-skin-tango .jcarousel-next-horizontal {
                    top: ". (intval(floor($thumbsize / 2.8))) ."px;
                }
                .jcarousel-skin-tango .jcarousel-prev-horizontal {
                    top: ". (intval(floor($thumbsize / 2.8))) ."px;
                }
                </style>\n
                <script type=\"text/javascript\">\n
                  jQuery(document).ready(function() {\n
                    jQuery('#navcarousel').jcarousel({\n
                        ". $ajaxhandler ."
                        itemFallbackDimension: ". ($thumbsize + 25) .",\n
                        start: ". $itemoffset .",\n
                        size: ". $childcount .",\n
                        visible: ". $showelements .",\n
                        ". $rtl_support ."
                        scroll: ". module::get_var("navcarousel", "scrollsize", "7") ."\n
                    });\n
                  ". $onwinload ."
                    $(\".jcarousel-prev-horizontal\").css(\"visibility\", \"visible\");\n
                    $(\".jcarousel-next-horizontal\").css(\"visibility\", \"visible\");\n
                    $(\"#navcarousel\").css(\"visibility\", \"visible\");\n
                    $(\"#navcarousel-wrapper\").css(\"background\", \"none\");\n
                    $(\"#navcarousel-wrapper\").css(\"float\", \"left\");\n
                  });\n
                </script>\n
                <!-- Navcaoursel -->";
  $thumbsize = module::get_var("navcarousel", "thumbsize", "50");
	$parent = $item->parent();
  $item_counter = 0;
  $item_offset = 0;
  $maintain_aspect = module::get_var("navcarousel", "maintainaspect", false);
  $no_resize = module::get_var("navcarousel", "noresize", false);
  $no_ajax = module::get_var("navcarousel", "noajax", false);
?>
<div id="navcarousel-wrapper">
  <ul id="navcarousel" class="jcarousel-skin-tango">
<?php
if (!$no_ajax) {
?>
  </ul>
</div>
<script type="text/javascript">
function navcarousel_itemLoadCallback(carousel, state)
{
    // Check if the requested items already exist
    if (carousel.has(carousel.first, carousel.last)) {
        return;
    }

    jQuery.get(
        '<?= url::site("navcarousel/item/". $item->id) ?>',
        {
            first: carousel.first,
            last: carousel.last
        },
        function(xml) {
            navcarousel_itemAddCallback(carousel, carousel.first, carousel.last, xml);
        },
        'xml'
    );
};

function navcarousel_itemAddCallback(carousel, first, last, xml)
{
    // Set the size of the carousel
    carousel.size(parseInt(jQuery('total', xml).text()));

    jQuery('image', xml).each(function(i) {
        carousel.add(first + i, navcarousel_getItemHTML(jQuery(this).text()));
    });
};

function navcarousel_getItemHTML(url)
{
    var thisurl='<?= $item->thumb_url() ?>';
    var linkCollection = new Object;

<?php
}
$totalitems = ORM::factory("item")->where("parent_id", "=", $parent->id)->where("type", "=", "photo")->count_all();
foreach ($parent->viewable()->children() as $photo) {
  if ($photo->is_album()) {
    continue;
  }
  if ($photo->id == $item->id) {
    $navcar_size_addition = 10;
  } else {
    $navcar_size_addition = 0;
  }
  if ($no_resize) {
    $navcar_divsize = "style=\"width: ". ($thumbsize + $navcar_size_addition) ."px; height: ". ($thumbsize + $navcar_size_addition) ."px;\"";
    if ($photo->width > $photo->height) {
      $navcar_thumbsize = "height=\"". ($thumbsize + $navcar_size_addition) ."\"";
    } else {
      $navcar_thumbsize = "width=\"". ($thumbsize + $navcar_size_addition) ."\"";
    }
  } else {
    $navcar_divsize = "";
    if ($maintain_aspect) {
      $navcar_thumbsize = photo::img_dimensions($photo->width, $photo->height, $thumbsize + $navcar_size_addition);
    } else {
      $navcar_thumbsize = "width=\"". ($thumbsize + $navcar_size_addition) ."\" height=\"". ($thumbsize + $navcar_size_addition) ."\"";
    }
  }
  if ($no_ajax) {
    if (module::get_var("navcarousel", "nomouseover", false)) {
      $img_title = "";
    } else {
      $img_title =  " title=\"". html::purify($photo->title)->for_html_attr() ." (". $parent->get_position($photo) . t("%position of %total", array("position" => "", "total" => $totalitems)) .")\"";
    }
    if ($item->id == $photo->id) {
      echo "<li><div class=\"g-button ui-corner-all ui-icon-left ui-state-hover carousel-current\" ". $navcar_divsize ."><div style=\"width: 100%; height: 100%; overflow: hidden;\"><img src=\"". $photo->thumb_url() ."\" alt=\"". html::purify($photo->title)->for_html_attr() ."\"". $img_title ." ". $navcar_thumbsize ." /></div></div></li>\n";
    } else {
      echo "<li><div class=\"g-button ui-corner-all ui-icon-left ui-state-default carousel-thumbnail\" ". $navcar_divsize ."><div style=\"width: 100%; height: 100%; overflow: hidden;\"><a href=\"". $photo->abs_url() ."\"><img src=\"". $photo->thumb_url() ."\" alt=\"". html::purify($photo->title)->for_html_attr() ."\"". $img_title ." ". $navcar_thumbsize ." /></a></div></div></li>\n";
    }
  } else {
    echo ("linkCollection['". $photo->thumb_url() ."'] = ['". $photo->abs_url() ."', '". html::purify($photo->title)->for_html_attr() ."', '". $parent->get_position($photo) ."', '". $navcar_thumbsize ."', '". $navcar_divsize ."'];\n");
  }
}
if ($no_ajax) {
  echo "
        </ul>\n
    </div>\n";
} else {
  if (module::get_var("navcarousel", "nomouseover", false)) {
    $img_title = "";
  } else {
    $img_title =  " title=\"' + linkCollection[url][1] + ' (' + linkCollection[url][2] + '". t("%position of %total", array("position" => "", "total" => $totalitems)) .")\"";
  }
  ?>
       if (thisurl==url)
        {
        return '<div class="g-button ui-corner-all ui-icon-left ui-state-hover carousel-current" ' + linkCollection[url][4] + '><div style="width: 100%; height: 100%; overflow: hidden;"><img src="' + url + '" alt="' + linkCollection[url][1] + '"<?= $img_title ?> ' + linkCollection[url][3] + ' /></div></div>';
        }
      else
        {
        return '<div class="g-button ui-corner-all ui-icon-left ui-state-default carousel-thumbnail" ' + linkCollection[url][4] + '><div style="width: 100%; height: 100%; overflow: hidden;"><a href="' + linkCollection[url][0] + '"><img src="' + url + '" alt="' + linkCollection[url][1] + '"<?= $img_title ?> ' + linkCollection[url][3] + ' /></a></div></div>';
        }
  };

  </script>
<?php  
}
