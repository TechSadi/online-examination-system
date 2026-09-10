<?php
/**
 * Create / edit exam form. One template serves both actions, so the two
 * cannot drift apart the way add_exam.php and edit_exam.php did.
 *
 * @var array<string,mixed>|null $exam        null when creating
 * @var array<string,mixed>      $old
 * @var int                      $maxDuration
 */
$isEdit = $exam !== null;
$value  = static fn (string $key, mixed $fallback = '') => (string) ($old[$key] ?? ($isEdit ? $exam[$key] : $fallback));
?>
<nav aria-label="Breadcrumb">
  <ol class="breadcrumb">
    <li><a href="<?= e(url('/admin/exams.php')) ?>">Exams</a></li>
    <li><?= icon('chevron-right') ?></li>
    <li aria-current="page"><?= $isEdit ? e($exam['title']) : 'New exam' ?></li>
  </ol>
</nav>

<div class="page-head">
  <div class="page-head-text">
    <h1 class="page-title"><?= $isEdit ? 'Edit exam' : 'New exam' ?></h1>
    <p class="page-subtitle">
      <?= $isEdit
          ? 'Change the title, description or duration. Existing attempts are not affected.'
          : 'Give the exam a name and a time limit. You will add its questions next.' ?>
    </p>
  </div>
</div>

<div class="card card-form">
  <div class="card-body">
    <form method="POST" novalidate data-loading>
      <?= csrf_field() ?>
      <?php if ($isEdit): ?>
        <input type="hidden" name="exam_id" value="<?= (int) $exam['exam_id'] ?>">
      <?php endif; ?>

      <div class="field">
        <label class="field-label" for="title">Exam title</label>
        <input type="text" id="title" name="title" class="field-input"
               placeholder="e.g. Introduction to Python"
               value="<?= e($value('title')) ?>" maxlength="150" required autofocus>
      </div>

      <div class="field">
        <label class="field-label" for="description">
          Description <span class="field-optional">Optional</span>
        </label>
        <textarea id="description" name="description" class="field-input" rows="3"
                  placeholder="What this exam covers, and anything a candidate should know before starting."
                  aria-describedby="description-hint"><?= e($value('description')) ?></textarea>
        <small class="field-hint" id="description-hint">Shown to students on the exam list.</small>
      </div>

      <div class="field">
        <label class="field-label" for="duration">Duration</label>
        <input type="number" id="duration" name="duration" class="field-input field-input-short"
               min="1" max="<?= (int) $maxDuration ?>" placeholder="30"
               value="<?= e($value('duration', '30')) ?>"
               aria-describedby="duration-hint" required>
        <small class="field-hint" id="duration-hint">
          In minutes, between 1 and <?= (int) $maxDuration ?>. The clock starts when a student opens the exam.
        </small>
      </div>

      <div class="form-actions">
        <button type="submit" class="btn btn-primary"
                data-loading-label="Saving&hellip;">
          <?= icon('save') ?> <?= $isEdit ? 'Save changes' : 'Create exam' ?>
        </button>
        <a class="btn btn-secondary" href="<?= e(url('/admin/exams.php')) ?>">Cancel</a>

        <?php if ($isEdit): ?>
          <a class="btn btn-ghost"
             href="<?= e(url('/admin/questions.php?exam_id=' . (int) $exam['exam_id'])) ?>">
            <?= icon('checklist') ?> Manage questions
          </a>
        <?php endif; ?>
      </div>
    </form>
  </div>
</div>
