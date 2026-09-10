/**
 * app.js - General UI helpers shared by every page.
 */
(function () {
  'use strict';

  document.addEventListener('DOMContentLoaded', function () {
    /* Auto-dismiss success alerts that opt in with data-auto-dismiss. */
    document.querySelectorAll('.alert[data-auto-dismiss]').forEach(function (alert) {
      setTimeout(function () {
        alert.style.transition = 'opacity .5s';
        alert.style.opacity = '0';
        setTimeout(function () {
          alert.remove();
        }, 500);
      }, 5000);
    });

    /*
     * Confirmation for destructive actions. These are submit buttons inside a
     * POST form, so cancelling simply stops the submission.
     */
    document.querySelectorAll('[data-confirm]').forEach(function (control) {
      control.addEventListener('click', function (event) {
        if (!window.confirm(this.dataset.confirm || 'Are you sure?')) {
          event.preventDefault();
        }
      });
    });
  });
})();
