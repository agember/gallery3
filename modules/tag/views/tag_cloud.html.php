<?php defined("SYSPATH") or die("No direct script access.") ?>
<h1>People</h1>

<!--<ul>
   <? foreach ($tags as $tag): ?>
   <li class="size<?=(int)(($tag->count / $max_count) * 7) ?>">
     <span><?= $tag->count ?> photos are tagged with </span>
     <a href="<?= $tag->url() ?>"><?= html::clean($tag->name) ?></a>
     &middot;
   </li>
   <? endforeach ?>
</ul>-->

<?= t2("There is one person", "There are %count people", count($tags)) ?>
<? $tags_per_column = count($tags)/3 ?>
<? $column_tag_count = 0 ?>

    <table>
      <tr>
        <td>
        <? foreach ($tags as $i => $tag): ?>
          <? $current_letter = strtoupper(mb_substr($tag->name, 0, 1)) ?>

          <? if ($i == 0): /* first letter */ ?>
          <strong><?= html::clean($current_letter) ?></strong>
          <ul>
          <? elseif ($last_letter != $current_letter): /* new letter */ ?>
          </ul>
            <? if ($column_tag_count > $tags_per_column): /* new column */ ?>
              <? $column_tag_count = 0 ?>
        </td>
        <td>
            <? endif ?>
          <strong><?= html::clean($current_letter) ?></strong>
          <ul>
          <? endif ?>
              <li>

           <a href="<?= $tag->url() ?>">
                <span class="g-tag-name" rel="<?= $tag->id ?>"><?= html::clean($tag->name) ?></span></a>
                <span class="g-understate">(<?= $tag->count ?>)</span>
              </li>
          <? $column_tag_count++ ?>
          <? $last_letter = $current_letter ?>
        <? endforeach ?>
          </ul>
        </td>
      </tr>
    </table>
