<?php
/** @var \App\Modules\Assessments\Models\Assessment $assessment @var array $units @var array $assignments */
$isEdit = $assessment->id !== null;
?>
<div class="d-flex justify-content-between align-items-center mb-3">
    <h1 class="h4 mb-0"><?= $isEdit ? 'Editar Evaluación' : 'Nueva Evaluación' ?></h1>
    <a href="<?= url('/assessments') ?>" class="btn btn-outline-secondary btn-sm">Volver</a>
</div>
<div class="card card-av"><div class="card-body">
    <form method="post" action="<?= $isEdit ? url('/assessments/' . $assessment->id) : url('/assessments') ?>">
        <?= csrf_field() ?>
        <div class="row g-3">
            <div class="col-md-8">
                <label class="form-label">Título *</label>
                <input type="text" name="title" class="form-control" value="<?= e(old('title', $assessment->title)) ?>" required>
            </div>
            <div class="col-md-4">
                <label class="form-label">Estado</label>
                <select name="status" class="form-select">
                    <?php foreach (['DRAFT', 'PUBLISHED', 'CLOSED'] as $st): ?>
                        <option value="<?= e($st) ?>" <?= selected(old('status', $assessment->status), $st) ?>><?= e($st) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-6">
                <label class="form-label">Asociar a Unidad</label>
                <select name="unit_id" class="form-select">
                    <option value="0">— Ninguna —</option>
                    <?php foreach ($units as $u): ?>
                        <option value="<?= (int) $u['id'] ?>" <?= selected(old('unit_id', $assessment->unit_id ?? 0), (int) $u['id']) ?>><?= e($u['title']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-6">
                <label class="form-label">Asociar a Asignación de Saber</label>
                <select name="saber_assignment_id" class="form-select">
                    <option value="0">— Ninguna —</option>
                    <?php foreach ($assignments as $a): ?>
                        <option value="<?= (int) $a['id'] ?>" <?= selected(old('saber_assignment_id', $assessment->saber_assignment_id ?? 0), (int) $a['id']) ?>><?= e($a['label']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label">Puntaje máximo *</label>
                <input type="number" step="0.01" name="max_score" class="form-control" value="<?= e(old('max_score', $assessment->max_score)) ?>" required>
            </div>
            <div class="col-md-3">
                <label class="form-label">Puntaje de aprobación *</label>
                <input type="number" step="0.01" name="passing_score" class="form-control" value="<?= e(old('passing_score', $assessment->passing_score)) ?>" required>
            </div>
            <div class="col-md-3">
                <label class="form-label">Intentos máximos</label>
                <input type="number" name="max_attempts" class="form-control" value="<?= e(old('max_attempts', $assessment->max_attempts ?? '')) ?>" placeholder="Sin límite">
            </div>
            <div class="col-md-3">
                <label class="form-label">Tiempo límite (minutos)</label>
                <input type="number" name="time_limit_minutes" class="form-control" value="<?= $assessment->time_limit !== null ? (int) round($assessment->time_limit / 60) : '' ?>" placeholder="Sin límite">
            </div>
            <div class="col-md-6">
                <label class="form-label">Disponible desde</label>
                <input type="datetime-local" name="available_from" class="form-control" value="<?= e(old('available_from', $assessment->available_from ?? '')) ?>">
            </div>
            <div class="col-md-6">
                <label class="form-label">Disponible hasta</label>
                <input type="datetime-local" name="available_until" class="form-control" value="<?= e(old('available_until', $assessment->available_until ?? '')) ?>">
            </div>
            <div class="col-12">
                <label class="form-label">Descripción</label>
                <textarea name="description" class="form-control" rows="2"><?= e(old('description', $assessment->description)) ?></textarea>
            </div>
            <div class="col-12">
                <button class="btn btn-primary"><?= $isEdit ? 'Actualizar' : 'Guardar' ?></button>
            </div>
        </div>
    </form>
</div></div>
