<?php
/** @var \App\Modules\Assessments\Models\Assessment $assessment @var array $questions @var array $types */
?>
<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h1 class="h4 mb-1">Preguntas</h1>
        <span class="text-muted"><?= e($assessment->title) ?></span>
    </div>
    <a href="<?= url('/assessments') ?>" class="btn btn-outline-secondary btn-sm">Volver</a>
</div>

<div class="row g-3">
    <div class="col-lg-7">
        <div class="card card-av"><div class="card-body">
            <?php if (! $questions): ?><p class="text-muted">Sin preguntas todavía.</p><?php endif; ?>
            <?php foreach ($questions as $q): ?>
                <div class="border rounded p-2 mb-2">
                    <div class="d-flex justify-content-between">
                        <div>
                            <span class="badge bg-primary"><?= e($q['type']) ?></span>
                            <strong class="ms-1"><?= e($q['question_text']) ?></strong>
                            <span class="text-muted small">(<?= e($q['points']) ?> pts)</span>
                        </div>
                        <form method="post" action="<?= url('/questions/' . $q['id'] . '/delete') ?>" onsubmit="return confirm('¿Eliminar pregunta?');">
                            <?= csrf_field() ?>
                            <button class="btn btn-sm btn-outline-danger">Eliminar</button>
                        </form>
                    </div>
                    <ul class="small mb-0 mt-1">
                        <?php foreach ($q['options'] as $o): ?>
                            <li><?= ! empty($o['is_correct']) ? '✔' : '○' ?> <?= e($o['option_text']) ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endforeach; ?>
        </div></div>
    </div>
    <div class="col-lg-5">
        <div class="card card-av"><div class="card-body">
            <h2 class="h6">Agregar pregunta</h2>
            <form method="post" action="<?= url('/assessments/' . $assessment->id . '/questions') ?>">
                <?= csrf_field() ?>
                <div class="mb-2">
                    <label class="form-label">Tipo</label>
                    <select name="type" class="form-select">
                        <option value="SINGLE_ANSWER">Respuesta única</option>
                        <option value="MULTIPLE_CHOICE">Selección múltiple</option>
                        <option value="TRUE_FALSE">Verdadero / Falso</option>
                    </select>
                </div>
                <div class="mb-2">
                    <label class="form-label">Pregunta</label>
                    <input type="text" name="question_text" class="form-control" required>
                </div>
                <div class="mb-2">
                    <label class="form-label">Puntos</label>
                    <input type="number" step="0.01" name="points" class="form-control" value="1" required>
                </div>
                <div class="mb-2">
                    <label class="form-label">Opciones (marca la correcta)</label>
                    <?php for ($i = 0; $i < 4; $i++): ?>
                        <div class="input-group input-group-sm mb-1">
                            <span class="input-group-text">
                                <input class="form-check-input mt-0" type="radio" name="options[<?= $i ?>][correct]" value="1" <?= $i === 0 ? 'checked' : '' ?> title="Correcta">
                            </span>
                            <input type="text" name="options[<?= $i ?>][text]" class="form-control" placeholder="Opción <?= $i + 1 ?>">
                        </div>
                    <?php endfor; ?>
                </div>
                <button class="btn btn-primary w-100">Agregar pregunta</button>
            </form>
        </div></div>
    </div>
</div>
