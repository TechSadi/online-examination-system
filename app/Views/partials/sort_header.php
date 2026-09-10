<?php
/**
 * A sortable column header.
 *
 * Rendered as a link, so a sorted view has its own URL and works without
 * JavaScript. aria-sort tells assistive technology which column the table is
 * ordered by and in which direction - the arrow alone does not.
 *
 * @var \App\Core\Sorter $sort
 * @var string           $key    the column key, from the repository allowlist
 * @var string           $label
 * @var string           $path   application path of the listing page
 */
$ariaSort = $sort->ariaSortFor($key);
?>
<th scope="col" <?= $ariaSort !== null ? 'aria-sort="' . e($ariaSort) . '"' : '' ?>>
  <a class="th-sort" href="<?= e(query_url($path, [
      'sort' => $key,
      'dir'  => $sort->directionFor($key),
      'page' => null,
  ])) ?>">
    <?= e($label) ?>
    <?php if ($ariaSort === null): ?>
      <?= icon('sort') ?>
    <?php else: ?>
      <?= icon($sort->direction === 'desc' ? 'arrow-down' : 'arrow-up') ?>
    <?php endif; ?>
  </a>
</th>
