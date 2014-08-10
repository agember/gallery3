<?php defined("SYSPATH") or die("No direct script access.") ?>
<style type="text/css">
input.rebuild_text {
width: 250px;
background-color: white;
}
</style>
<?= (t('Back to: <a href="%url">%img</a>', 
		array("img" => $item->title, "url" => $item->url())));
?>
<div id="g-admin-comment-block-block">
  <h2><?= t("Rebuild item administration") ?></h2>
  <p><?= t("This acctually marks the item as 'dirty' and Gallery will rebuild that item in the adminstration section.") ?></p>
  <?= $form ?>
</div>
<script>
// Make checkboxes like radio buttons.  Forge does not have radio buttons.
$('input.g-unique').click(function() {
    $('input.g-unique:checked').not(this).removeAttr('checked');
});
</script>
