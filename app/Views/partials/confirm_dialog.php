<?php
/**
 * The confirmation asked before any destructive action.
 *
 * One dialog serves the whole document; app.js fills in the title, the
 * consequence and the button label from the control that opened it. That is
 * what keeps "delete this exam" and "delete this student" asking in the same
 * voice, and it replaces window.confirm(), which cannot be styled, cannot
 * spell out a consequence over two lines, and looks like a browser warning
 * rather than part of the application.
 *
 * With JavaScript unavailable, app.js falls back to window.confirm() and this
 * markup is simply never opened.
 */
?>
<dialog class="modal" id="confirm-dialog" aria-labelledby="confirm-dialog-title">
  <div class="modal-body">
    <div class="modal-icon"><?= icon('warning') ?></div>
    <div>
      <h2 class="modal-title" id="confirm-dialog-title" data-confirm-title>Are you sure?</h2>
      <p class="modal-text" data-confirm-text></p>
    </div>
  </div>
  <div class="modal-footer">
    <button type="button" class="btn btn-secondary" data-dialog-cancel>Cancel</button>
    <button type="button" class="btn btn-danger" data-confirm-accept>
      <span data-confirm-accept-label>Delete</span>
    </button>
  </div>
</dialog>
