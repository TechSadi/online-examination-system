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
<div class="page-heading">
  <h2><?= $isEdit ? 'Edit Exam' : 'Add New Exam' ?></h2>
  <p class="text-muted">
    <?= $isEdit ? 'Update the exam details below.' : 'Fill in the details below to create a new examination.' ?>
  </p>
</div>

<div class="card card-narrow">
  <div class="card-header">
    <?= $isEdit ? '&#9999; Edit: ' . e($exam['title']) : '&#128203; Exam Details' ?>
  </div>
  <div class="card-body">
    <form method="POST" novalidate>
      <?php if ($isEdit): ?>
        <input type="hidden" name="exam_id" value="<?= (int) $exam['exam_id'] ?>">
      <?php endif; ?>

      <div class="form-group">
        <label for="title">Exam Title *</label>
        <input type="text" id="title" name="title" class="form-control"
               placeholder="e.g. Introduction to Python" value="<?= e($value('title')) ?>" required>
      </div>

      <div class="form-group">
        <label for="description">Description</label>
        <textarea id="description" name="description" class="form-control"
                  placeholder="Brief description of this exam (optional)"><?= e($value('description')) ?></textarea>
      </div>

      <div class="form-group">
        <label for="duration">Duration (minutes) *</label>
        <input type="number" id="duration" name="duration" class="form-control"
               min="1" max="<?= (int) $maxDuration ?>" placeholder="30"
               value="<?= e($value('duration', '30')) ?>" required>
        <small class="form-hint">Between 1 and <?= (int) $maxDuration ?> minutes.</small>
      </div>

      <div class="form-actions">
        <button type="submit" class="btn btn-primary">
          <?= $isEdit ? '&#128190; Save Changes' : '&#128190; Save &amp; Add Questions &rarr;' ?>
        </button>
        <?php if ($isEdit): ?>
          <a href="<?= e(url('/admin/questions.php?exam_id=' . (int) $exam['exam_id'])) ?>"
             class="btn btn-outline">Manage Questions</a>
        <?php endif; ?>
        <a href="<?= e(url('/admin/exams.php')) ?>" class="btn btn-outline">Cancel</a>
      </div>
    </form>
  </div>
</div>
