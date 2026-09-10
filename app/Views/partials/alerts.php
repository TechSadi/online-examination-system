<?php
/**
 * Flash messages and validation errors.
 *
 * Renders whatever Flash has queued for this request. Replaces the per-page
 * "?msg=deleted" if/elseif chains that each page used to carry.
 *
 * @var list<array{type:string,message:string}> $flashes
 * @var list<string>                            $errors
 */
$flashes = $flashes ?? [];
$errors  = $errors  ?? [];
?>
<?php foreach ($flashes as $flash): ?>
  <div class="alert alert-<?= e($flash['type']) ?>" role="status"<?= $flash['type'] === 'success' ? ' data-auto-dismiss' : '' ?>>
    <?= e($flash['message']) ?>
  </div>
<?php endforeach; ?>

<?php if ($errors !== []): ?>
  <div class="alert alert-danger" role="alert">
    <div>
      <strong>Please fix the following:</strong>
      <ul class="alert-list">
        <?php foreach ($errors as $error): ?>
          <li><?= e($error) ?></li>
        <?php endforeach; ?>
      </ul>
    </div>
  </div>
<?php endif; ?>
