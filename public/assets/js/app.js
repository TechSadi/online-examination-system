/**
 * app.js - the interaction behaviours shared by every page.
 *
 * Five small features, each driven by a data attribute so that markup opts
 * in rather than this file knowing about individual pages:
 *
 *   [data-dismiss] / [data-auto-dismiss]  alerts
 *   [data-confirm]                        destructive actions
 *   [data-loading]                        forms that report their own submit
 *   [data-menu]                           dropdown menus
 *   [data-drawer-toggle]                  the navigation drawer
 *
 * Everything degrades: with JavaScript unavailable, alerts stay on screen,
 * forms post normally, and the destructive-action confirmation falls back to
 * the browser's own. No behaviour here is load-bearing for security - every
 * decision that matters is taken again on the server.
 */
(function () {
  'use strict';

  var AUTO_DISMISS_AFTER = 6000;

  /* ── Alerts ───────────────────────────────────────────── */

  function closeAlert(alert) {
    alert.style.transition = 'opacity 200ms, transform 200ms';
    alert.style.opacity = '0';
    alert.style.transform = 'translateY(-4px)';
    window.setTimeout(function () { alert.remove(); }, 200);
  }

  function initAlerts() {
    document.querySelectorAll('.alert [data-dismiss]').forEach(function (button) {
      button.addEventListener('click', function () {
        closeAlert(button.closest('.alert'));
      });
    });

    /* Only confirmations dismiss themselves. An error stays until it is read
       and dealt with. */
    document.querySelectorAll('.alert[data-auto-dismiss]').forEach(function (alert) {
      window.setTimeout(function () { closeAlert(alert); }, AUTO_DISMISS_AFTER);
    });
  }

  /* ── Loading states ───────────────────────────────────── */

  /**
   * Put a submit button into its loading state.
   *
   * The button is not disabled: a disabled control is dropped from the POST,
   * which would lose the name/value some of these carry. It is marked
   * aria-disabled and guarded against a second click instead.
   */
  function startLoading(button) {
    if (!button || button.classList.contains('is-loading')) {
      return;
    }

    var label = button.getAttribute('data-loading-label');

    if (label) {
      button.setAttribute('aria-label', label);
    }

    button.classList.add('is-loading');
    button.setAttribute('aria-disabled', 'true');
  }

  function initLoadingForms() {
    document.querySelectorAll('form[data-loading]').forEach(function (form) {
      form.addEventListener('submit', function (event) {
        if (form.dataset.submitted === 'true') {
          event.preventDefault();
          return;
        }

        /* A form the browser has already rejected on constraint validation
           never reaches its handler, so this only runs on a real submit. */
        form.dataset.submitted = 'true';
        startLoading(form.querySelector('button[type="submit"], input[type="submit"]'));
      });
    });
  }

  /* ── Destructive confirmation ─────────────────────────── */

  /* One dialog per document, filled in from the button that opened it.
     Deleting an exam and deleting a student ask the same way, and the
     consequence is spelled out rather than left as "Are you sure?". */

  var confirmDialog = null;
  var pendingButton = null;

  function initConfirm() {
    confirmDialog = document.getElementById('confirm-dialog');

    document.addEventListener('click', function (event) {
      var trigger = event.target.closest('[data-confirm]');

      if (!trigger) {
        return;
      }

      var message = trigger.getAttribute('data-confirm');

      if (!confirmDialog || typeof confirmDialog.showModal !== 'function') {
        if (!window.confirm(message)) {
          event.preventDefault();
        }
        return;
      }

      event.preventDefault();
      pendingButton = trigger;

      confirmDialog.querySelector('[data-confirm-title]').textContent =
        trigger.getAttribute('data-confirm-title') || 'Are you sure?';
      confirmDialog.querySelector('[data-confirm-text]').textContent = message;

      var accept = confirmDialog.querySelector('[data-confirm-accept]');
      accept.querySelector('[data-confirm-accept-label]').textContent =
        trigger.getAttribute('data-confirm-label') || 'Delete';
      accept.classList.remove('is-loading');
      accept.removeAttribute('aria-disabled');

      confirmDialog.showModal();
    });

    if (!confirmDialog) {
      return;
    }

    confirmDialog.querySelectorAll('[data-dialog-cancel]').forEach(function (button) {
      button.addEventListener('click', function () { confirmDialog.close(); });
    });

    confirmDialog.querySelector('[data-confirm-accept]').addEventListener('click', function (event) {
      if (!pendingButton) {
        return;
      }

      startLoading(event.currentTarget);

      var form = pendingButton.form || pendingButton.closest('form');

      if (!form) {
        pendingButton.click();
        confirmDialog.close();
        return;
      }

      /* requestSubmit(button) posts the button's own name and value, which
         is how these forms carry `action=delete`. form.submit() would drop
         it and the request would do nothing. */
      if (typeof form.requestSubmit === 'function') {
        form.requestSubmit(pendingButton);
      } else {
        form.submit();
      }
    });

    /* Escape, the backdrop, or Cancel: release the pending action. */
    confirmDialog.addEventListener('close', function () { pendingButton = null; });
  }

  /* ── Dropdown menus ───────────────────────────────────── */

  function closeMenu(menu) {
    menu.querySelector('[data-menu-panel]').hidden = true;
    menu.querySelector('[data-menu-trigger]').setAttribute('aria-expanded', 'false');
  }

  function initMenus() {
    var menus = Array.prototype.slice.call(document.querySelectorAll('[data-menu]'));

    if (menus.length === 0) {
      return;
    }

    menus.forEach(function (menu) {
      var trigger = menu.querySelector('[data-menu-trigger]');
      var panel = menu.querySelector('[data-menu-panel]');

      trigger.addEventListener('click', function () {
        var open = trigger.getAttribute('aria-expanded') === 'true';

        menus.forEach(closeMenu);

        if (!open) {
          panel.hidden = false;
          trigger.setAttribute('aria-expanded', 'true');
        }
      });
    });

    document.addEventListener('click', function (event) {
      menus.forEach(function (menu) {
        if (!menu.contains(event.target)) {
          closeMenu(menu);
        }
      });
    });

    document.addEventListener('keydown', function (event) {
      if (event.key !== 'Escape') {
        return;
      }

      menus.forEach(function (menu) {
        if (menu.querySelector('[data-menu-trigger]').getAttribute('aria-expanded') === 'true') {
          closeMenu(menu);
          menu.querySelector('[data-menu-trigger]').focus();
        }
      });
    });
  }

  /* ── Navigation drawer ────────────────────────────────── */

  /* Below 1024px the admin sidebar is off-canvas. Focus moves into it when
     it opens and back to the button that opened it when it closes, so the
     drawer is usable from the keyboard and not merely visible. */

  function initDrawer() {
    var toggle = document.querySelector('[data-drawer-toggle]');
    var drawer = toggle && document.getElementById(toggle.getAttribute('aria-controls'));

    if (!toggle || !drawer) {
      return;
    }

    var scrim = null;

    function open() {
      drawer.classList.add('is-open');
      toggle.setAttribute('aria-expanded', 'true');
      document.body.classList.add('has-drawer-open');

      scrim = document.createElement('div');
      scrim.className = 'drawer-scrim';
      scrim.addEventListener('click', close);
      document.body.appendChild(scrim);

      var first = drawer.querySelector('a, button');
      if (first) {
        first.focus();
      }
    }

    function close(options) {
      drawer.classList.remove('is-open');
      toggle.setAttribute('aria-expanded', 'false');
      document.body.classList.remove('has-drawer-open');

      if (scrim) {
        scrim.remove();
        scrim = null;
      }

      if (!options || options.returnFocus !== false) {
        toggle.focus();
      }
    }

    toggle.addEventListener('click', function () {
      if (drawer.classList.contains('is-open')) {
        close();
      } else {
        open();
      }
    });

    drawer.querySelectorAll('[data-drawer-close]').forEach(function (button) {
      button.addEventListener('click', function () { close(); });
    });

    document.addEventListener('keydown', function (event) {
      if (event.key === 'Escape' && drawer.classList.contains('is-open')) {
        close();
      }
    });

    /* Resizing past the breakpoint turns the drawer back into a permanent
       sidebar; the scrim and the scroll lock must not survive that. */
    window.matchMedia('(min-width: 1024px)').addEventListener('change', function (event) {
      if (event.matches && drawer.classList.contains('is-open')) {
        close({ returnFocus: false });
      }
    });
  }

  function init() {
    initAlerts();
    initLoadingForms();
    initConfirm();
    initMenus();
    initDrawer();
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
  } else {
    init();
  }
})();
