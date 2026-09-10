<?php
/**
 * Pagination for a listing.
 *
 * Every link is built with query_url(), so paging keeps whatever search,
 * filter and sort the table is currently showing.
 *
 * @var \App\Core\Paginator $paginator
 * @var string              $path   application path of the listing page
 * @var string              $noun   what is being counted, singular
 */
if (!$paginator->hasPages()) {
    return;
}
?>
<nav class="pagination" aria-label="Pagination">
  <p class="pagination-summary">
    Showing <?= $paginator->from() ?>&ndash;<?= $paginator->to() ?>
    of <?= pluralise($paginator->total, $noun) ?>
  </p>

  <div class="pagination-pages">
    <?php if ($paginator->hasPrevious()): ?>
      <a class="pagination-page" rel="prev" aria-label="Previous page"
         href="<?= e(query_url($path, ['page' => $paginator->page - 1])) ?>"><?= icon('chevron-left') ?></a>
    <?php else: ?>
      <span class="pagination-page is-disabled" aria-hidden="true"><?= icon('chevron-left') ?></span>
    <?php endif; ?>

    <?php foreach ($paginator->window() as $number): ?>
      <?php if ($number === null): ?>
        <span class="pagination-gap" aria-hidden="true">&hellip;</span>
      <?php elseif ($number === $paginator->page): ?>
        <a class="pagination-page" aria-current="page"
           href="<?= e(query_url($path, ['page' => $number])) ?>"><?= $number ?></a>
      <?php else: ?>
        <a class="pagination-page" aria-label="Page <?= $number ?>"
           href="<?= e(query_url($path, ['page' => $number])) ?>"><?= $number ?></a>
      <?php endif; ?>
    <?php endforeach; ?>

    <?php if ($paginator->hasNext()): ?>
      <a class="pagination-page" rel="next" aria-label="Next page"
         href="<?= e(query_url($path, ['page' => $paginator->page + 1])) ?>"><?= icon('chevron-right') ?></a>
    <?php else: ?>
      <span class="pagination-page is-disabled" aria-hidden="true"><?= icon('chevron-right') ?></span>
    <?php endif; ?>
  </div>
</nav>
