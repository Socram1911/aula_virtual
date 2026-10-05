<?php
/** @var \App\Modules\AcademicPeriods\Models\AcademicPeriod $period */
$isEdit = $period->id !== null;
?>
<div class="d-flex justify-content-between align-items-center mb-3">
    <h1 class="h4 mb-0"><?= $isEdit ? 'Editar Período' : 'Nuevo Período' ?></h1>
    <a href="<?= url('/academic-periods') ?>" class="btn btn-outline-secondary btn-sm">Volver</a>
</div>

<div class="card card-av"><div class="card-body">
    <form method="post" action="<?= $isEdit ? url('/academic-periods/' . $period->id) : url('/academic-periods') ?>">
        <?= csrf_field() ?>
        <div class="row g-3">
            <div class="col-md-6">
                <label class="form-label">Nombre *</label>
                <input type="text" name="name" class="form-control" value="<?= e(old('name', $period->name)) ?>" required>
            </div>
            <div class="col-md-6">
                <label class="form-label">Código *</label>
                <input type="text" name="code" class="form-control" value="<?= e(old('code', $period->code)) ?>" required>
            </div>
            <div class="col-md-6">
                <label class="form-label">Fecha de inicio *</label>
                <input type="date" name="start_date" class="form-control" value="<?= e(old('start_date', $period->start_date)) ?>" required>
            </div>
            <div class="col-md-6">
                <label class="form-label">Fecha de fin *</label>
                <input type="date" name="end_date" class="form-control" value="<?= e(old('end_date', $period->end_date)) ?>" required>
            </div>
            <div class="col-md-6">
                <label class="form-label">Estado</label>
                <select name="status" class="form-select">
                    <?php foreach (\App\Modules\AcademicPeriods\Models\AcademicPeriod::STATUSES as $s): ?>
                        <option value="<?= e($s) ?>" <?= selected(old('status', $period->status), $s) ?>><?= e($s) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-12">
                <button class="btn btn-primary"><?= $isEdit ? 'Actualizar' : 'Guardar' ?></button>
            </div>
        </div>
    </form>
</div></div>
