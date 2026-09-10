/**
 * app.js – General UI helpers
 */
(function () {
  'use strict';

  /* Auto-dismiss alerts after 4 s */
  document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('.alert[data-auto-dismiss]').forEach(function (el) {
      setTimeout(function () {
        el.style.transition = 'opacity .5s';
        el.style.opacity = '0';
        setTimeout(function () { el.remove(); }, 500);
      }, 4000);
    });

    /* Confirm-delete buttons */
    document.querySelectorAll('[data-confirm]').forEach(function (btn) {
      btn.addEventListener('click', function (e) {
        if (!window.confirm(this.dataset.confirm || 'Are you sure?')) {
          e.preventDefault();
        }
      });
    });

    /* Active nav link */
    const currentPath = window.location.pathname;
    document.querySelectorAll('.navbar-links a, .sidebar-menu a').forEach(function (link) {
      if (link.getAttribute('href') && currentPath.includes(link.getAttribute('href').split('/').pop())) {
        link.classList.add('active');
      }
    });
  });
})();
