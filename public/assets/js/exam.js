/**
 * exam.js - Exam countdown timer and question navigation.
 *
 * Reads its configuration from the DOM:
 *   #timer-display[data-seconds-remaining]  time left, calculated by the server
 *   .question-slide[data-index]             one per question
 *   .q-nav-btn[data-index]                  question navigator buttons
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
 * answer key in the database; nothing here influences the score.
 */
(function () {
  'use strict';

  var currentQuestion = 0;
  var timerInterval = null;
  var secondsLeft = 0;

  var timerDisplay, timerWidget, timerLabel;
  var questionSlides, navButtons;
  var prevBtn, nextBtn, submitBtn;
  var progressFill, progressLabel;

  /* Timer */

  function formatTime(totalSeconds) {
    var minutes = Math.floor(totalSeconds / 60);
    var seconds = totalSeconds % 60;
    return String(minutes).padStart(2, '0') + ':' + String(seconds).padStart(2, '0');
  }

  function updateTimerStyle() {
    timerWidget.classList.remove('warning', 'danger');
    if (secondsLeft <= 60) {
      timerWidget.classList.add('danger');
    } else if (secondsLeft <= 300) {
      timerWidget.classList.add('warning');
    }
  }

  function tick() {
    secondsLeft -= 1;
    timerDisplay.textContent = formatTime(Math.max(0, secondsLeft));
    updateTimerStyle();

    if (secondsLeft <= 0) {
      clearInterval(timerInterval);
      timerLabel.textContent = 'Time Up!';
      autoSubmit();
    }
  }

  function startTimer(remainingSeconds) {
    secondsLeft = remainingSeconds;
    timerDisplay.textContent = formatTime(Math.max(0, secondsLeft));
    updateTimerStyle();

    if (secondsLeft <= 0) {
      timerLabel.textContent = 'Time Up!';
      autoSubmit();
      return;
    }

    timerInterval = setInterval(tick, 1000);
  }

  /* Answer tracking */

  function answeredIndexes() {
    var answered = [];
    questionSlides.forEach(function (slide, index) {
      if (slide.querySelector('input[type="radio"]:checked')) {
        answered.push(index);
      }
    });
    return answered;
  }

  function updateProgress() {
    var answered = answeredIndexes();

    answered.forEach(function (index) {
      if (navButtons[index]) {
        navButtons[index].classList.add('answered');
      }
    });

    if (progressFill) {
      progressFill.style.width =
        Math.round((answered.length / questionSlides.length) * 100) + '%';
    }
    if (progressLabel) {
      progressLabel.textContent = answered.length + ' / ' + questionSlides.length + ' answered';
    }
  }

  /* Navigation */

  function showQuestion(index) {
    if (index < 0 || index >= questionSlides.length) {
      return;
    }

    questionSlides.forEach(function (slide) {
      slide.classList.remove('active');
    });
    questionSlides[index].classList.add('active');

    navButtons.forEach(function (button, i) {
      button.classList.toggle('current', i === index);
      button.setAttribute('aria-current', i === index ? 'true' : 'false');
    });

    currentQuestion = index;

    var isLast = index === questionSlides.length - 1;
    prevBtn.disabled = index === 0;
    nextBtn.hidden = isLast;
    submitBtn.hidden = !isLast;
  }

  function bindOptions() {
    document.querySelectorAll('.option-item').forEach(function (item) {
      item.addEventListener('click', function () {
        var radio = this.querySelector('input[type="radio"]');
        if (!radio) {
          return;
        }

        this.closest('.options-list')
          .querySelectorAll('.option-item')
          .forEach(function (sibling) {
            sibling.classList.remove('selected');
          });

        this.classList.add('selected');
        radio.checked = true;
        updateProgress();
      });
    });
  }

  /* Submission */

  function doSubmit() {
    clearInterval(timerInterval);
    window.removeEventListener('beforeunload', warnBeforeUnload);

    submitBtn.disabled = true;
    submitBtn.textContent = 'Submitting...';

    document.getElementById('exam-form').submit();
  }

  function autoSubmit() {
    var toast = document.createElement('div');
    toast.className = 'alert alert-warning exam-toast';
    toast.setAttribute('role', 'alert');
    toast.innerHTML = '&#9200; <strong>Time is up!</strong> Your exam is being submitted...';
    document.body.appendChild(toast);
    setTimeout(doSubmit, 1800);
  }

  function confirmSubmit() {
    var unanswered = questionSlides.length - answeredIndexes().length;
    var message = 'Are you sure you want to submit the exam?';

    if (unanswered > 0) {
      message = 'You have ' + unanswered + ' unanswered question(s). Submit anyway?';
    }

    if (window.confirm(message)) {
      doSubmit();
    }
  }

  function warnBeforeUnload(event) {
    if (answeredIndexes().length < questionSlides.length) {
      event.preventDefault();
      event.returnValue = '';
    }
  }

  /* Init */

  document.addEventListener('DOMContentLoaded', function () {
    timerDisplay = document.getElementById('timer-display');
    timerWidget = document.getElementById('timer-widget');
    timerLabel = document.getElementById('timer-label');
    prevBtn = document.getElementById('btn-prev');
    nextBtn = document.getElementById('btn-next');
    submitBtn = document.getElementById('btn-submit');
    progressFill = document.getElementById('exam-progress-fill');
    progressLabel = document.getElementById('exam-progress-label');

    questionSlides = Array.prototype.slice.call(document.querySelectorAll('.question-slide'));
    navButtons = Array.prototype.slice.call(document.querySelectorAll('.q-nav-btn'));

    if (!timerDisplay || questionSlides.length === 0 || !prevBtn || !nextBtn || !submitBtn) {
      return;
    }

    prevBtn.addEventListener('click', function () {
      showQuestion(currentQuestion - 1);
    });
    nextBtn.addEventListener('click', function () {
      showQuestion(currentQuestion + 1);
    });
    submitBtn.addEventListener('click', confirmSubmit);

    navButtons.forEach(function (button, index) {
      button.addEventListener('click', function () {
        showQuestion(index);
      });
    });

    document.addEventListener('keydown', function (event) {
      if (event.target.matches('input, textarea, select')) {
        return;
      }
      if (event.key === 'ArrowRight') {
        showQuestion(currentQuestion + 1);
      }
      if (event.key === 'ArrowLeft') {
        showQuestion(currentQuestion - 1);
      }
    });

    bindOptions();
    showQuestion(0);
    updateProgress();
    var remaining = parseInt(timerDisplay.dataset.secondsRemaining, 10);
    startTimer(isNaN(remaining) ? 0 : remaining);

    window.addEventListener('beforeunload', warnBeforeUnload);
  });
})();
