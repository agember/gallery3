<?php defined("SYSPATH") or die("No direct script access.") ?>
<div id="g-album-header">
  <div id="g-album-header-buttons">
    <?= $theme->dynamic_top() ?>
  </div>
  <h1><?= html::clean($title) ?></h1>
</div>

<ul id="g-album-grid" class="ui-helper-clearfix">
  <? foreach ($children as $i => $child): ?>
  <li class="g-item <?= $child->is_album() ? "g-album" : "" ?>">
    <?= $theme->thumb_top($child) ?>
      <img id="g-photo-id-<?= $child->id ?>" class="g-thumbnail"
           alt="photo" src="<?= $child->thumb_url() ?>"
           width="<?= $child->thumb_width ?>"
           height="<?= $child->thumb_height ?>" />
<ul class="g-context-menu">
<li><b><i><a href="#">Duplicate images found in:</a></i></b>
<ul>
<?php

//
// this is ugly code...
//

$firstdupe = db::select("itemmd5")
	  ->from("fullsize_md5sums")
	  ->where("item_id", "=", $child->id)
	  ->execute()->get("itemmd5");

foreach( db::build()
	  ->select("item_id")
	  ->from("fullsize_md5sums")
	  ->where("itemmd5", "=", $firstdupe)
	  ->execute() as $row){
	    foreach ( ORM::factory("item")
			->where("id", "=", $row->item_id)
			->find_all() as $did) {
	      echo "<li><a class=\"ui-icon-arrow-1-e\" href=\"".$did->abs_url()."\">";
	      echo $did->relative_url();
	      echo "</a></li>\n";
	    }
}
?>
</ul>
</li>
</ul>
  </li>
  <? endforeach ?>
</ul>
<?= $theme->dynamic_bottom() ?>

<?= $theme->paginator() ?>
