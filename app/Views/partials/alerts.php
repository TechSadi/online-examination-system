<?php
/**
 * Flash messages and validation errors.
 *
 * Renders whatever Flash has queued for this request. Replaces the per-page
 * "?msg=deleted" if/elseif chains that each page used to carry.
 *
 * Each severity gets a matching icon, so the message is distinguishable
 * without relying on its colour alone. role="alert" interrupts a screen
 * reader for errors, which are the messages that need answering; successes
 * use role="status", which waits for a pause.
 *
 * @var list<array{type:string,message:string}> $flashes
 * @var list<string>                            $errors
 */
$flashes = $flashes ?? [];
$errors  = $errors  ?? [];

$icons = [
    'success' => 'check-circle',
    'danger'  => 'x-circle',
    'warning' => 'warning',
    'info'    => 'info',
];
?>
<?php foreach ($flashes as $flash): ?>
  <?php $type = isset($icons[$flash['type']]) ? $flash['type'] : 'info'; ?>
  <div class="alert alert-<?= e($type) ?>" role="<?= $type === 'danger' ? 'alert' : 'status' ?>"
       <?= $type === 'success' ? 'data-auto-dismiss' : '' ?>>
    <?= icon($icons[$type]) ?>
    <div class="alert-body"><?= e($flash['message']) ?></div>
    <button type="button" class="alert-dismiss" data-dismiss aria-label="Dismiss message">
      <?= icon('x') ?>
    </button>
  </div>
<?php endforeach; ?>

<?php if ($errors !== []): ?>
  <div class="alert alert-danger" role="alert">
    <?= icon('x-circle') ?>
    <div class="alert-body">
      <p class="alert-title">
        <?= count($errors) === 1 ? 'There is a problem with this form' : 'There are ' . count($errors) . ' problems with this form' ?>
      </p>
      <ul class="alert-list">
        <?php foreach ($errors as $error): ?>
          <li><?= e($error) ?></li>
        <?php endforeach; ?>
      </ul>
    </div>
  </div>
<?php endif; ?>
