<?php
/**
 * The light / dark / system control in the top bar.
 *
 * Built on <details> rather than the scripted .menu used by the account
 * control, because this one has to work with JavaScript turned off: a reader
 * who needs the light theme to read the screen should not need scripting to
 * ask for it. <details> gives open, close, click and keyboard from the
 * platform; app.js only adds click-outside, Escape, and switching without a
 * page load.
 *
 * Each option is a submit button in a POST form carrying a CSRF token, so
 * changing the theme is a state-changing request like any other. The hidden
 * redirect field returns the reader to the page they were on; the server
 * refuses anything that is not a local path.
 */

use App\Core\Theme;
use App\Core\Url;

$current = Theme::current();
$options = Theme::options();

/* The trigger shows the theme in force, so the control reports its own state
   rather than being a mystery button. */
$triggerIcon = $options[$current][0];
?>
<details class="menu theme-picker" data-theme-picker>
  <summary class="theme-trigger" title="Theme"
           aria-label="Theme: <?= e($options[$current][1]) ?>">
    <?= icon($triggerIcon) ?>
    <?= icon('chevron-down', 'theme-trigger-caret') ?>
  </summary>

  <div class="menu-panel menu-panel-sm">
    <p class="menu-header menu-header-sm">Appearance</p>

    <form method="POST" action="<?= e(url('/theme.php')) ?>">
      <?= csrf_field() ?>
      <input type="hidden" name="redirect" value="<?= e(Url::currentWithQuery()) ?>">

      <?php foreach ($options as $value => [$iconName, $label]): ?>
        <button type="submit" name="theme" value="<?= e($value) ?>"
                class="menu-item theme-option"
                data-theme-value="<?= e($value) ?>"
                aria-pressed="<?= $current === $value ? 'true' : 'false' ?>">
          <?= icon($iconName) ?>
          <span><?= e($label) ?></span>
          <?= icon('check', 'theme-option-check') ?>
        </button>
      <?php endforeach; ?>
    </form>
  </div>
</details>
