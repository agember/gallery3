<?php defined("SYSPATH") or die("No direct script access.") ?> 
<style type="text/css">
input.rebuild_text {
width: 250px;
background-color: white;
}
input.rebuild_number {
width: 50px;
background-color: white;
}
</style>
<?= (t('Back to: <a href="%url">%img</a>', 
		array("img" => $item->title, "url" => $item->url())));
?>
<div id="g-admin-comment-block-block">
  <h2><?= t("Rebuild items administration") ?></h2>
  <p><?= t("This module acctually marks the items in the album as 'dirty' and Gallery will rebuild those items in the adminstration section.<br>
			On some hosts with limited resources the image toolkit generating the thumbs and resizes fails.  <br>
			At this time Gallery can't detect this failure.<br>
			If some of your thumbs & resizes are full sized you can let this module detect the oversize items and have them marked for rebuilding.<br>
			This saves resoureses if some thumbs/resizes have already been generated properly.") ?></p>
  <?= $form ?>
</div>
<script>
// Make checkboxes like radio buttons.  Forge does not have radio buttons.
$('input.g-unique').click(function() {
    $('input.g-unique:checked').not(this).removeAttr('checked');
});
</script>