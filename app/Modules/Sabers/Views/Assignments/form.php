<?php
/** @var ?\App\Modules\Sabers\Models\SaberAssignment $assignment @var array $sabers @var array $sections @var array $periods @var array $teachers @var array $selectedTeachers @var int $primaryId */
$isEdit = $assignment !== null;
$saberId = old('saber_id', $assignment?->saber_id ?? 0);
$sectionId = old('section_id', $assignment?->section_id ?? 0);
?>
<div class="d-flex justify-content-between align-items-center mb-3">
    <h1 class="h4 mb-0"><?= $isEdit ? 'Editar Asignación' : 'Nueva Asignación' ?></h1>
    <a href="<?= url('/saber-assignments') ?>" class="btn btn-outline-secondary btn-sm">Volver</a>
</div>
<div class="card card-av"><div class="card-body">
    <form method="post" action="<?= $isEdit ? url('/saber-assignments/' . $assignment->id) : url('/saber-assignments') ?>">
        <?= csrf_field() ?>
        <div class="row g-3">
            <div class="col-md-4">
                <label class="form-label">Saber *</label>
                <select name="saber_id" class="form-select" required <?= $isEdit ? 'disabled' : '' ?>>
                    <option value="">Seleccione…</option>
                    <?php foreach ($sabers as $s): ?>
                        <option value="<?= (int) $s['id'] ?>" <?= selected($saberId, (int) $s['id']) ?>><?= e($s['name']) ?></option>
                    <?php endforeach; ?>
                </select>
                <?php if ($isEdit): ?><input type="hidden" name="saber_id" value="<?= (int) $assignment->saber_id ?>"><?php endif; ?>
            </div>
            <div class="col-md-4">
                <label class="form-label">Sección *</label>
                <select name="section_id" class="form-select" required <?= $isEdit ? 'disabled' : '' ?>>
                    <option value="">Seleccione…</option>
                    <?php foreach ($sections as $sec): ?>
                        <option value="<?= (int) $sec['id'] ?>" <?= selected($sectionId, (int) $sec['id']) ?>>Sección <?= e($sec['number']) ?></option>
                    <?php endforeach; ?>
                </select>
                <?php if ($isEdit): ?><input type="hidden" name="section_id" value="<?= (int) $assignment->section_id ?>"><?php endif; ?>
            </div>
            <div class="col-md-4">
                <label class="form-label">Período *</label>
                <select name="academic_period_id" class="form-select" required <?= $isEdit ? 'disabled' : '' ?>>
                    <option value="">Seleccione…</option>
                    <?php foreach ($periods as $p): ?>
                        <option value="<?= (int) $p['id'] ?>" <?= selected(old('academic_period_id', $assignment?->academic_period_id ?? 0), (int) $p['id']) ?>><?= e($p['name']) ?></option>
                    <?php endforeach; ?>
                </select>
                <?php if ($isEdit): ?><input type="hidden" name="academic_period_id" value="<?= (int) $assignment->academic_period_id ?>"><?php endif; ?>
            </div>
            <div class="col-md-3">
                <label class="form-label">Inicio</label>
                <input type="date" name="start_date" class="form-control" value="<?= e(old('start_date', $assignment?->start_date ?? '')) ?>">
            </div>
            <div class="col-md-3">
                <label class="form-label">Fin</label>
                <input type="date" name="end_date" class="form-control" value="<?= e(old('end_date', $assignment?->end_date ?? '')) ?>">
            </div>
            <div class="col-md-3">
                <label class="form-label">Estado</label>
                <select name="status" class="form-select">
                    <?php foreach (['DRAFT', 'PUBLISHED', 'CLOSED'] as $st): ?>
                        <option value="<?= e($st) ?>" <?= selected(old('status', $assignment?->status ?? 'DRAFT'), $st) ?>><?= e($st) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-12">
                <label class="form-label">Docentes asignados</label>
                <div class="row">
                    <?php foreach ($teachers as $t): ?>
                        <div class="col-md-4 form-check">
                            <input class="form-check-input teacher-check" type="checkbox" name="teacher_ids[]" value="<?= (int) $t['id'] ?>"
                                   id="t<?= (int) $t['id'] ?>" <?= in_array((int) $t['id'], array_map('intval', old('teacher_ids', $selectedTeachers)), true) ? 'checked' : '' ?>>
                            <label class="form-check-label" for="t<?= (int) $t['id'] ?>"><?= e($t['last_name'] . ', ' . $t['first_name']) ?></label>
                        </div>
                    <?php endforeach; ?>
                </div>
                <label class="form-label mt-2">Docente principal</label>
                <select name="primary_teacher_id" class="form-select" style="max-width:300px">
                    <option value="0">Ninguno</option>
                    <?php foreach ($teachers as $t): ?>
                        <option value="<?= (int) $t['id'] ?>" <?= selected($primaryId, (int) $t['id']) ?>><?= e($t['last_name'] . ', ' . $t['first_name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-12">
                <button class="btn btn-primary"><?= $isEdit ? 'Actualizar' : 'Guardar' ?></button>
            </div>
        </div>
    </form>
</div></div>
