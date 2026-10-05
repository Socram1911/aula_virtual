<?php
/** @var \App\Modules\Sections\Models\Section $section @var array $periods @var array $tracks */
$isEdit = $section->id !== null;
?>
<div class="d-flex justify-content-between align-items-center mb-3">
    <h1 class="h4 mb-0"><?= $isEdit ? 'Editar Sección' : 'Nueva Sección' ?></h1>
    <a href="<?= url('/sections') ?>" class="btn btn-outline-secondary btn-sm">Volver</a>
</div>
<div class="card card-av"><div class="card-body">
    <form method="post" action="<?= $isEdit ? url('/sections/' . $section->id) : url('/sections') ?>">
        <?= csrf_field() ?>
        <div class="row g-3">
            <div class="col-md-4">
                <label class="form-label">Período académico *</label>
                <select name="academic_period_id" class="form-select" required>
                    <option value="">Seleccione…</option>
                    <?php foreach ($periods as $p): ?>
                        <option value="<?= (int) $p['id'] ?>" <?= selected(old('academic_period_id', $section->academic_period_id), (int) $p['id']) ?>><?= e($p['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-4">
                <label class="form-label">Trayecto *</label>
                <select name="track_id" class="form-select" required>
                    <option value="">Seleccione…</option>
                    <?php foreach ($tracks as $t): ?>
                        <option value="<?= (int) $t['id'] ?>" <?= selected(old('track_id', $section->track_id), (int) $t['id']) ?>><?= e($t['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-4">
                <label class="form-label">Número de sección *</label>
                <input type="number" name="number" class="form-control" min="1" value="<?= e(old('number', $section->number)) ?>" required>
            </div>
            <div class="col-md-6">
                <label class="form-label">Nombre</label>
                <input type="text" name="name" class="form-control" value="<?= e(old('name', $section->name)) ?>" placeholder="Sección 20">
            </div>
            <div class="col-md-6">
                <label class="form-label">Estado</label>
                <select name="status" class="form-select">
                    <option value="ACTIVE" <?= selected(old('status', $section->status), 'ACTIVE') ?>>Activa</option>
                    <option value="INACTIVE" <?= selected(old('status', $section->status), 'INACTIVE') ?>>Inactiva</option>
                </select>
            </div>
            <div class="col-12">
                <label class="form-label">Descripción</label>
                <textarea name="description" class="form-control" rows="2"><?= e(old('description', $section->description)) ?></textarea>
            </div>
            <div class="col-12">
                <button class="btn btn-primary"><?= $isEdit ? 'Actualizar' : 'Guardar' ?></button>
            </div>
        </div>
    </form>
</div></div>
