/**
 * exam.js – Exam countdown timer & question navigation
 *
 * Depends on:  window.EXAM_CONFIG  (set inline in take_exam.php)
 *   {
 *     duration: <minutes>,
 *     totalQuestions: <int>,
 *     submitUrl: 'submit_exam.php'
 *   }
 */

(function () {
  'use strict';

  /* ── State ──────────────────────────────────────────── */
  let currentQuestion = 0;
  const answers       = {};   // { questionIndex: optionValue }
  let timerInterval   = null;
  let secondsLeft     = 0;

  /* ── DOM refs (populated after DOMContentLoaded) ──── */
  let timerDisplay, timerWidget, timerLabel;
  let questionCards, navBtns;
  let prevBtn, nextBtn, submitBtn;

  /* ─────────────────────────────────────────────────── */

  /** Format seconds → MM:SS */
  function formatTime(s) {
    const m = Math.floor(s / 60);
    const sec = s % 60;
    return String(m).padStart(2, '0') + ':' + String(sec).padStart(2, '0');
  }

  /** Update the timer widget colour based on time remaining */
  function updateTimerStyle() {
    timerWidget.classList.remove('warning', 'danger');
    if (secondsLeft <= 60) {
      timerWidget.classList.add('danger');
    } else if (secondsLeft <= 300) {
      timerWidget.classList.add('warning');
    }
  }

  /** Tick – called every second */
  function tick() {
    secondsLeft--;
    timerDisplay.textContent = formatTime(secondsLeft);
    updateTimerStyle();

    if (secondsLeft <= 0) {
      clearInterval(timerInterval);
      timerLabel.textContent = 'Time Up!';
      autoSubmit();
    }
  }

  /** Start the countdown timer */
  function startTimer(durationMinutes) {
    secondsLeft = durationMinutes * 60;
    timerDisplay.textContent = formatTime(secondsLeft);
    timerInterval = setInterval(tick, 1000);
  }

  /* ── Question Navigation ────────────────────────────── */

  /** Show question at index i */
  function showQuestion(i) {
    // Hide all, show target
    questionCards.forEach(card => card.classList.remove('active'));
    questionCards[i].classList.add('active');

    // Update nav buttons
    navBtns.forEach((btn, idx) => {
      btn.classList.remove('current');
      if (idx === i) btn.classList.add('current');
    });

    currentQuestion = i;

    // Prev / Next visibility
    prevBtn.disabled = i === 0;
    nextBtn.disabled = i === questionCards.length - 1;

    // Show submit only on last question
    submitBtn.style.display = (i === questionCards.length - 1) ? 'inline-flex' : 'none';
    nextBtn.style.display    = (i === questionCards.length - 1) ? 'none' : 'inline-flex';
  }

  /** Mark a nav button as answered */
  function markAnswered(i) {
    navBtns[i].classList.add('answered');
  }

  /* ── Option Selection ───────────────────────────────── */

  function bindOptions() {
    document.querySelectorAll('.option-item').forEach(item => {
      item.addEventListener('click', function () {
        const radio  = this.querySelector('input[type="radio"]');
        const qIndex = parseInt(this.closest('.question-slide').dataset.index, 10);

        // Deselect siblings
        this.closest('.options-list').querySelectorAll('.option-item')
            .forEach(el => el.classList.remove('selected'));

        // Select this
        this.classList.add('selected');
        radio.checked = true;

        // Record answer
        answers[qIndex] = radio.value;

        // Mark nav btn
        markAnswered(qIndex);
      });
    });
  }

  /* ── Submit ─────────────────────────────────────────── */

  /** Collect all radio-checked answers and post the form */
  function doSubmit() {
    clearInterval(timerInterval);

    // Write answers into hidden inputs then submit the real form
    const form = document.getElementById('exam-form');
    Object.entries(answers).forEach(([qIdx, val]) => {
      // find existing hidden input or create one
      let hidden = form.querySelector(`input[name="answers[${qIdx}]"]`);
      if (!hidden) {
        hidden = document.createElement('input');
        hidden.type  = 'hidden';
        hidden.name  = `answers[${qIdx}]`;
        form.appendChild(hidden);
      }
      hidden.value = val;
    });

    form.submit();
  }

  function autoSubmit() {
    // Show toast
    const toast = document.createElement('div');
    toast.className = 'alert alert-warning';
    toast.style.cssText = 'position:fixed;top:76px;left:50%;transform:translateX(-50%);z-index:9999;min-width:320px;box-shadow:0 4px 20px rgba(0,0,0,.2)';
    toast.innerHTML = '⏰ <strong>Time is up!</strong> Your exam is being submitted…';
    document.body.appendChild(toast);
    setTimeout(doSubmit, 1800);
  }

  /** Confirm before manual submit */
  function confirmSubmit() {
    const unanswered = questionCards.length - Object.keys(answers).length;
    let msg = 'Are you sure you want to submit the exam?';
    if (unanswered > 0) {
      msg = `You have ${unanswered} unanswered question(s). Submit anyway?`;
    }
    if (window.confirm(msg)) doSubmit();
  }

  /* ── Progress Bar ───────────────────────────────────── */
  function updateProgress() {
    const fill = document.getElementById('exam-progress-fill');
    if (!fill) return;
    const pct = Math.round((Object.keys(answers).length / questionCards.length) * 100);
    fill.style.width = pct + '%';
    const label = document.getElementById('exam-progress-label');
    if (label) label.textContent = Object.keys(answers).length + ' / ' + questionCards.length + ' answered';
  }

  // Patch answers to also update progress
  const _markAnswered = markAnswered;
  markAnswered = function (i) {
    _markAnswered(i);
    updateProgress();
  };

  /* ── Init ───────────────────────────────────────────── */

  document.addEventListener('DOMContentLoaded', function () {
    timerDisplay = document.getElementById('timer-display');
    timerWidget  = document.getElementById('timer-widget');
    timerLabel   = document.getElementById('timer-label');
    questionCards = Array.from(document.querySelectorAll('.question-slide'));
    navBtns      = Array.from(document.querySelectorAll('.q-nav-btn'));
    prevBtn      = document.getElementById('btn-prev');
    nextBtn      = document.getElementById('btn-next');
    submitBtn    = document.getElementById('btn-submit');

    if (!timerDisplay || questionCards.length === 0) return;

    // Keyboard navigation
    document.addEventListener('keydown', function (e) {
      if (e.key === 'ArrowRight' && currentQuestion < questionCards.length - 1) showQuestion(currentQuestion + 1);
      if (e.key === 'ArrowLeft'  && currentQuestion > 0) showQuestion(currentQuestion - 1);
    });

    prevBtn.addEventListener('click', () => showQuestion(currentQuestion - 1));
    nextBtn.addEventListener('click', () => showQuestion(currentQuestion + 1));
    submitBtn.addEventListener('click', confirmSubmit);

    navBtns.forEach((btn, i) => {
      btn.addEventListener('click', () => showQuestion(i));
    });

    bindOptions();

    // Start from Q1
    showQuestion(0);

    // Start timer
    const duration = parseInt(timerDisplay.dataset.duration, 10) || 30;
    startTimer(duration);

    // Warn before leaving page
    window.addEventListener('beforeunload', function (e) {
      if (Object.keys(answers).length < questionCards.length) {
        e.preventDefault();
        e.returnValue = '';
      }
    });
  });

})();
