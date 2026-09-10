<?php
/**
 * The account control at the end of the top bar.
 *
 * It replaces the standalone "Logout" button that used to sit in the
 * navigation. Signing out is not a destination, so it belongs behind the
 * account it signs you out of - and putting it there stops it being the
 * button nearest a student's thumb during an exam.
 *
 * The panel is plain markup with `hidden` on it; app.js toggles that and
 * keeps aria-expanded in step. With JavaScript unavailable the panel is
 * simply never opened, so the sign-out form is also rendered as a normal
 * control inside it rather than being built by script.
 *
 * @var string $name       display name of the signed-in person
 * @var string $roleLabel  'Student' | 'Administrator'
 * @var string $logoutPath application path to post the sign-out to
 */
$panelId = 'account-menu';
?>
<div class="menu" data-menu>
  <button type="button" class="account-trigger" data-menu-trigger
          aria-haspopup="true" aria-expanded="false" aria-controls="<?= e($panelId) ?>">
    <span class="avatar" aria-hidden="true"><?= e(initials($name)) ?></span>
    <span class="account-name"><?= e($name) ?></span>
    <span class="sr-only">Account menu</span>
    <?= icon('chevron-down') ?>
  </button>

  <div class="menu-panel" id="<?= e($panelId) ?>" data-menu-panel hidden>
    <div class="menu-header">
      <p class="menu-name"><?= e($name) ?></p>
      <p class="menu-meta"><?= e($roleLabel) ?></p>
    </div>

    <form method="POST" action="<?= e(url($logoutPath)) ?>" class="inline-form-block">
      <?= csrf_field() ?>
      <button type="submit" class="menu-item menu-item-danger">
        <?= icon('logout') ?> Sign out
      </button>
    </form>
  </div>
</div>
