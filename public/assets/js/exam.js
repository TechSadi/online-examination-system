/**
 * exam.js - the examination interface.
 *
 * Reads its configuration from the DOM:
 *   #timer-display[data-seconds-remaining]  time left, calculated by the server
 *   .question[data-index]                   one section per question
 *   .q-nav[data-index]                      navigator buttons
 *   [data-flag][data-index]                 per-question flag toggles
 *
 * The countdown is a convenience, not a control. The authoritative deadline
 * is exam_attempts.expires_at, fixed when the attempt began and re-checked
 * when the answers arrive. Editing the number below, pausing the interval, or
 * blocking this file entirely buys no extra time: a submission that reaches
 * the server late is recorded as expired whatever the page believed.
 *
 * Starting from a server-supplied remaining time rather than the exam's full
 * duration is what makes a reload resume the same clock instead of restarting
 * it.
 *
 * The answers a student picks are carried by the radio inputs themselves,
 * which the form posts normally. Grading happens server-side against the
 * answer key in the database; nothing here influences the score. Flags are
 * a reading aid only - they are never submitted.
 */
(function () {
  'use strict';

  var WARNING_AT  = 300;  /* five minutes */
  var CRITICAL_AT = 60;   /* one minute   */

  /* Milestones announced to assistive technology. Announcing every tick
     would make a screen reader read the clock aloud continuously, which is
     unusable; these are the moments that actually change a decision. */
  var ANNOUNCE_AT = [600, 300, 60, 30];

  var current = 0;
  var secondsLeft = 0;
  var interval = null;
  var submitting = false;
  var flags = Object.create(null);
  var storageKey = null;

  var form, timerEl, timerValue, announcer;
  var questions, navButtons, flagButtons;
  var prevBtn, nextBtn, submitBtn, confirmBtn;
  var progressFill, progressLabel;
  var dialog, unansweredBox, overlay;

  /* ── Time ─────────────────────────────────────────────── */

  function formatTime(total) {
    var safe = Math.max(0, total);
    var minutes = Math.floor(safe / 60);
    var seconds = safe % 60;

    return String(minutes).padStart(2, '0') + ':' + String(seconds).padStart(2, '0');
  }

  /** "4 minutes" / "30 seconds", for the spoken announcement. */
  function describe(total) {
    if (total >= 60) {
      var minutes = Math.round(total / 60);
      return minutes + (minutes === 1 ? ' minute' : ' minutes');
    }

    return total + ' seconds';
  }

  function paintTimer() {
    timerValue.textContent = formatTime(secondsLeft);

    timerEl.classList.toggle('is-warning', secondsLeft <= WARNING_AT && secondsLeft > CRITICAL_AT);
    timerEl.classList.toggle('is-critical', secondsLeft <= CRITICAL_AT);
  }

  function tick() {
    secondsLeft -= 1;
    paintTimer();

    if (ANNOUNCE_AT.indexOf(secondsLeft) !== -1) {
      announcer.textContent = describe(secondsLeft) + ' remaining in this exam.';
    }

    if (secondsLeft <= 0) {
      window.clearInterval(interval);
      submitExam(true);
    }
  }

  function startTimer(remaining) {
    secondsLeft = remaining;
    paintTimer();

    if (secondsLeft <= 0) {
      submitExam(true);
      return;
    }

    interval = window.setInterval(tick, 1000);
  }

  /* ── Answers ──────────────────────────────────────────── */

  function isAnswered(index) {
    return questions[index].querySelector('input[type="radio"]:checked') !== null;
  }

  function answeredCount() {
    return questions.reduce(function (total, _question, index) {
      return total + (isAnswered(index) ? 1 : 0);
    }, 0);
  }

  function unansweredIndexes() {
    var pending = [];

    questions.forEach(function (_question, index) {
      if (!isAnswered(index)) {
        pending.push(index);
      }
    });

    return pending;
  }

  /* ── Flags ────────────────────────────────────────────── */

  /* Flags survive a reload of the same attempt. They are per-tab state about
     how the student is reading the paper, so sessionStorage is the right
     home for them: nothing here belongs on the server. */

  function loadFlags() {
    if (!storageKey) {
      return;
    }

    try {
      var stored = window.sessionStorage.getItem(storageKey);

      (stored ? JSON.parse(stored) : []).forEach(function (index) {
        flags[index] = true;
      });
    } catch (error) {
      /* Private browsing, a disabled store, or corrupt JSON. Flags are an
         aid, not state the exam depends on, so losing them is harmless. */
    }
  }

  function saveFlags() {
    if (!storageKey) {
      return;
    }

    try {
      window.sessionStorage.setItem(storageKey, JSON.stringify(Object.keys(flags).map(Number)));
    } catch (error) {
      /* See loadFlags. */
    }
  }

  function toggleFlag(index) {
    if (flags[index]) {
      delete flags[index];
    } else {
      flags[index] = true;
    }

    saveFlags();
    paintFlag(index);
    paintNavigator();
  }

  function paintFlag(index) {
    var button = flagButtons[index];

    if (!button) {
      return;
    }

    var flagged = Boolean(flags[index]);
    var label = button.querySelector('[data-flag-label]');

    button.setAttribute('aria-pressed', flagged ? 'true' : 'false');
    button.classList.toggle('is-flagged', flagged);

    if (label) {
      label.textContent = flagged ? 'Flagged' : 'Flag';
    }
  }

  /* ── Navigator and progress ───────────────────────────── */

  function paintNavigator() {
    navButtons.forEach(function (button, index) {
      var answered = isAnswered(index);
      var flagged = Boolean(flags[index]);

      button.classList.toggle('is-answered', answered);
      button.classList.toggle('is-current', index === current);
      button.classList.toggle('is-flagged', flagged);

      /* The state has to be in the accessible name too: the colour of the
         tile is not available to a screen reader. */
      button.setAttribute(
        'aria-label',
        'Question ' + (index + 1) + ', ' + (answered ? 'answered' : 'unanswered')
          + (flagged ? ', flagged' : '')
          + (index === current ? ', current' : '')
      );
      button.setAttribute('aria-current', index === current ? 'true' : 'false');
    });
  }

  function paintProgress() {
    var answered = answeredCount();

    progressFill.style.width = Math.round((answered / questions.length) * 100) + '%';
    progressLabel.textContent = answered + ' of ' + questions.length + ' answered';
  }

  /* ── Navigation ───────────────────────────────────────── */

  function show(index) {
    if (index < 0 || index >= questions.length) {
      return;
    }

    questions.forEach(function (question, i) {
      question.classList.toggle('is-active', i === index);
    });

    current = index;

    var isLast = index === questions.length - 1;

    prevBtn.disabled = index === 0;
    nextBtn.hidden = isLast;
    submitBtn.hidden = !isLast;

    paintNavigator();
  }

  /* ── Submission ───────────────────────────────────────── */

  function openSubmitDialog() {
    var pending = unansweredIndexes();

    if (pending.length === 0) {
      unansweredBox.hidden = true;
    } else {
      unansweredBox.hidden = false;
      unansweredBox.className = 'submit-summary';
      unansweredBox.textContent = '';

      var heading = document.createElement('p');
      heading.textContent = pending.length === 1
        ? 'One question is still unanswered:'
        : pending.length + ' questions are still unanswered:';
      unansweredBox.appendChild(heading);

      var list = document.createElement('p');
      list.className = 'submit-summary-list';

      pending.forEach(function (index) {
        var tag = document.createElement('span');
        tag.textContent = String(index + 1);
        list.appendChild(tag);
      });

      unansweredBox.appendChild(list);
    }

    if (typeof dialog.showModal === 'function') {
      dialog.showModal();
    } else {
      /* No <dialog> support: fall back to the browser's own confirmation
         rather than submitting an exam without asking. */
      if (window.confirm('Submit your exam? You cannot return to these questions.')) {
        submitExam(false);
      }
    }
  }

  /**
   * Hand the paper in.
   *
   * @param {boolean} expired true when the clock ran out rather than the
   *                          student choosing to submit.
   */
  function submitExam(expired) {
    if (submitting) {
      return;
    }

    submitting = true;
    window.clearInterval(interval);
    window.removeEventListener('beforeunload', warnBeforeUnload);

    if (dialog.open) {
      dialog.close();
    }

    overlay.querySelector('p').textContent = expired
      ? 'Time is up. Submitting your answers…'
      : 'Submitting your answers…';
    overlay.hidden = false;

    /* Submitted immediately. There is no pause for effect here: on an expired
       attempt every second of delay is a second closer to the grace period
       running out. */
    form.submit();
  }

  function warnBeforeUnload(event) {
    if (submitting) {
      return;
    }

    event.preventDefault();
    event.returnValue = '';
  }

  /* ── Init ─────────────────────────────────────────────── */

  function init() {
    form          = document.getElementById('exam-form');
    timerEl       = document.getElementById('exam-timer');
    timerValue    = document.getElementById('timer-display');
    announcer     = document.getElementById('timer-announcer');
    prevBtn       = document.getElementById('btn-prev');
    nextBtn       = document.getElementById('btn-next');
    submitBtn     = document.getElementById('btn-submit');
    confirmBtn    = document.getElementById('confirm-submit');
    progressFill  = document.getElementById('exam-progress-fill');
    progressLabel = document.getElementById('exam-progress-label');
    dialog        = document.getElementById('submit-dialog');
    unansweredBox = document.getElementById('submit-unanswered');
    overlay       = document.getElementById('exam-submitting');

    questions   = Array.prototype.slice.call(document.querySelectorAll('.question'));
    navButtons  = Array.prototype.slice.call(document.querySelectorAll('.q-nav'));
    flagButtons = Array.prototype.slice.call(document.querySelectorAll('[data-flag]'));

    if (!form || !timerValue || questions.length === 0 || !prevBtn || !nextBtn || !submitBtn) {
      return;
    }

    var examField = form.querySelector('input[name="exam_id"]');
    storageKey = examField ? 'examhub.flags.' + examField.value : null;

    prevBtn.addEventListener('click', function () { show(current - 1); });
    nextBtn.addEventListener('click', function () { show(current + 1); });
    submitBtn.addEventListener('click', openSubmitDialog);

    if (confirmBtn) {
      confirmBtn.addEventListener('click', function () { submitExam(false); });
    }

    dialog.querySelectorAll('[data-dialog-cancel]').forEach(function (button) {
      button.addEventListener('click', function () { dialog.close(); });
    });

    navButtons.forEach(function (button, index) {
      button.addEventListener('click', function () { show(index); });
    });

    flagButtons.forEach(function (button, index) {
      button.addEventListener('click', function () { toggleFlag(index); });
    });

    /* Delegated, so it covers every radio on the page with one listener. */
    form.addEventListener('change', function (event) {
      if (event.target.matches('input[type="radio"]')) {
        paintProgress();
        paintNavigator();
      }
    });

    document.addEventListener('keydown', function (event) {
      if (event.altKey || event.ctrlKey || event.metaKey) {
        return;
      }

      /* Never steal an arrow key from a control that uses it: the radio
         group in the current question is navigated with arrows. */
      if (event.target.closest('input, textarea, select, dialog')) {
        return;
      }

      if (event.key === 'ArrowRight') {
        show(current + 1);
      } else if (event.key === 'ArrowLeft') {
        show(current - 1);
      }
    });

    loadFlags();
    flagButtons.forEach(function (_button, index) { paintFlag(index); });

    show(0);
    paintProgress();
    paintNavigator();

    var remaining = parseInt(timerValue.dataset.secondsRemaining, 10);
    startTimer(isNaN(remaining) ? 0 : remaining);

    window.addEventListener('beforeunload', warnBeforeUnload);
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
  } else {
    init();
  }
})();
