<?php defined("SYSPATH") or die("No direct script access.") ?>
<div id="g-tag-cloud-page-header">
  <div id="g-tag-cloud-page-buttons">
    <?= $theme->dynamic_top() ?>
  </div>
  <h1><?= html::clean($title) ?></h1>
</div>
<br />

<? if (module::is_active("tag_cloud")) { ?>
<script type="text/javascript">
  $("document").ready(function() {
    $("#g-tag-cloud-page").gallery_tag_cloud_page({
      movie: "<?= url::file("modules/tag_cloud/lib/tagcloud.swf") ?>"
      <? foreach ($options as $option => $value) : ?>
        , <?= $option ?> : "<?= $value ?>"
      <? endforeach ?>
    });
  });
</script>
<div id="g-tag-cloud-page">
  <div id="g-tag-cloud-page-animation" ref="<?= url::site("tag_cloud_page") ?>">
    <div id="g-tag-cloud-movie-page">
      <?= tag::cloud(ORM::factory("tag")->count_all()); ?>
    </div>
  </div>
</div>

<? } else { ?>
<div id="g-tag-cloud-page">
<ul>
  <? 
   $tags = tag::cloud(ORM::factory("tag")->count_all());
   foreach ($tags as $tag): ?>
  <li class="size<?=(int)(($tag->count / $max_count) * 7) ?>">
    <span><?= $tag->count ?> photos are tagged with </span>
     <a href="<?= $tag->url() ?>"><?= html::clean($tag->name) ?></a>
     &middot;
  </li>
  <? endforeach ?>
</ul>
<!--  <?= tag::cloud(ORM::factory("tag")->count_all()); ?>-->
</div>
<? } ?>

<?= $theme->dynamic_bottom() ?>
