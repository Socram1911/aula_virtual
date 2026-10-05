<?php
/** @var \App\Modules\Assessments\Models\Assessment $assessment @var array $payload */
$questions = $payload['questions'] ?? [];
?>
<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h1 class="h4 mb-1"><?= e($assessment->title) ?></h1>
        <span class="text-muted">Intento #<?= (int) ($payload['attempt_number'] ?? 1) ?></span>
        <?php if (! empty($payload['time_limit_seconds'])): ?>
            <span class="badge bg-warning text-dark ms-2">Tiempo límite: <?= (int) round($payload['time_limit_seconds'] / 60) ?> min</span>
        <?php endif; ?>
    </div>
</div>

<form method="post" action="<?= url('/assessments/' . $assessment->id . '/attempt') ?>">
    <?= csrf_field() ?>
    <input type="hidden" name="attempt_id" value="<?= (int) ($payload['attempt_id'] ?? 0) ?>">

    <?php if (! $questions): ?>
        <div class="alert alert-info">Esta evaluación no tiene preguntas.</div>
    <?php endif; ?>

    <?php foreach ($questions as $i => $q): ?>
        <div class="card card-av mb-3">
            <div class="card-body">
                <p class="fw-semibold"><?= (int) ($i + 1) ?>. <?= e($q['question_text']) ?> <span class="text-muted small">(<?= e($q['points']) ?> pts)</span></p>
                <?php $multiple = $q['type'] === 'MULTIPLE_CHOICE'; ?>
                <?php foreach ($q['options'] as $o): ?>
                    <div class="form-check">
                        <input class="form-check-input" type="<?= $multiple ? 'checkbox' : 'radio' ?>"
                               name="answers[<?= (int) $q['id'] ?>][]" value="<?= (int) $o['id'] ?>" id="q<?= (int) $q['id'] ?>_<?= (int) $o['id'] ?>">
                        <label class="form-check-label" for="q<?= (int) $q['id'] ?>_<?= (int) $o['id'] ?>"><?= e($o['option_text']) ?></label>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endforeach; ?>

    <button class="btn btn-primary">Enviar evaluación</button>
</form>
