<?php
/**
 * Sign-out control.
 *
 * Signing out changes state, so it is a POST carrying a CSRF token, not a
 * link. As a GET link, any page on the internet could sign a user out with
 * an <img src="...logout.php"> tag.
 *
 * Used by both the navbar and the admin sidebar, which style their controls
 * differently - hence the caller-supplied class.
 *
 * @var string $action Application path to post to.
 * @var string $class  CSS class for the button.
 * @var string $label  Button content. A hardcoded literal from the caller,
 *                     never user data, so its HTML entities render as-is.
 */
?>
<form method="POST" action="<?= e(url($action)) ?>" class="logout-form">
  <?= csrf_field() ?>
  <button type="submit" class="<?= e($class) ?>"><?= $label ?></button>
</form>
