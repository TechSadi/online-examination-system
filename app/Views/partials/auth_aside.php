<?php
/**
 * The context panel beside a sign-in form.
 *
 * Shared by the three authentication screens so the three do not drift into
 * three different looks. Hidden below 900px, where it would only push the
 * form the visitor came for below the fold.
 *
 * @var string                       $quote
 * @var list<array{0:string,1:string,2:string}> $points icon, title, body
 */
?>
<aside class="auth-aside" aria-hidden="true">
  <blockquote><?= e($quote) ?></blockquote>

  <div class="auth-points">
    <?php foreach ($points as [$iconName, $title, $body]): ?>
      <div class="auth-point">
        <?= icon($iconName) ?>
        <p><strong><?= e($title) ?></strong><?= e($body) ?></p>
      </div>
    <?php endforeach; ?>
  </div>
</aside>
