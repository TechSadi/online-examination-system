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
 *   [data-theme-picker]                   light / dark / system
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

  /* ── Theme ────────────────────────────────────────────── */

  /* The server has already stamped the theme onto <html> from the cookie, so
     the page arrived in the right colours and there is nothing to correct on
     load. This only handles changing it: write the same cookie, move the
     attribute, and repaint in place rather than making a round trip for a
     preference. The <details> element the picker is built on opens and closes
     by itself; what it does not give is click-outside or Escape, so those are
     added here.

     With scripting off, the form inside the picker posts to theme.php and the
     server does exactly the same thing. */

  var THEME_COOKIE = 'examhub_theme';
  var THEME_MAX_AGE = 31536000;

  function writeThemeCookie(theme, basePath) {
    var attributes = '; path=' + basePath + '; samesite=lax'
      + (window.location.protocol === 'https:' ? '; secure' : '');

    if (theme === 'system') {
      document.cookie = THEME_COOKIE + '=; max-age=0' + attributes;
      return;
    }

    document.cookie = THEME_COOKIE + '=' + theme + '; max-age=' + THEME_MAX_AGE + attributes;
  }

  function applyTheme(picker, theme) {
    if (theme === 'system') {
      document.documentElement.removeAttribute('data-theme');
    } else {
      document.documentElement.setAttribute('data-theme', theme);
    }

    picker.querySelectorAll('[data-theme-value]').forEach(function (option) {
      var chosen = option.getAttribute('data-theme-value') === theme;
      option.setAttribute('aria-pressed', chosen ? 'true' : 'false');

      if (chosen) {
        /* The trigger reports the theme in force, so its icon and its
           accessible name follow the choice. */
        var summary = picker.querySelector('summary');
        var label = option.querySelector('span');

        summary.querySelector('use').setAttribute(
          'href',
          option.querySelector('use').getAttribute('href')
        );

        if (label) {
          summary.setAttribute('aria-label', 'Theme: ' + label.textContent.trim());
        }
      }
    });
  }

  function initTheme() {
    var picker = document.querySelector('[data-theme-picker]');

    if (!picker) {
      return;
    }

    /* The cookie is scoped to the application, not the host, so it does not
       follow the reader into the next project sharing this origin. The form's
       own action carries that base path already. */
    var action = picker.querySelector('form').getAttribute('action');
    var basePath = action.replace(/\/theme\.php$/, '') + '/';

    picker.addEventListener('click', function (event) {
      var option = event.target.closest('[data-theme-value]');

      if (!option) {
        return;
      }

      event.preventDefault();

      var theme = option.getAttribute('data-theme-value');
      writeThemeCookie(theme, basePath);
      applyTheme(picker, theme);
      picker.open = false;
    });

    document.addEventListener('click', function (event) {
      if (picker.open && !picker.contains(event.target)) {
        picker.open = false;
      }
    });

    document.addEventListener('keydown', function (event) {
      if (event.key === 'Escape' && picker.open) {
        picker.open = false;
        picker.querySelector('summary').focus();
      }
    });
  }

  function init() {
    initAlerts();
    initLoadingForms();
    initConfirm();
    initMenus();
    initDrawer();
    initTheme();
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
  } else {
    init();
  }
})();
