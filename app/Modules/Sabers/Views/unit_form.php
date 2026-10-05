<?php
/** @var \App\Modules\Sabers\Models\Saber $saber @var \App\Modules\Sabers\Models\Unit $unit */
$isEdit = $unit->id !== null;
?>
<div class="d-flex justify-content-between align-items-center mb-3">
    <h1 class="h4 mb-0"><?= $isEdit ? 'Editar Unidad' : 'Nueva Unidad' ?> — <?= e($saber->name) ?></h1>
    <a href="<?= url('/sabers/' . $saber->id . '/units') ?>" class="btn btn-outline-secondary btn-sm">Volver</a>
</div>
<div class="card card-av"><div class="card-body">
    <form method="post" action="<?= $isEdit ? url('/units/' . $unit->id) : url('/sabers/' . $saber->id . '/units') ?>">
        <?= csrf_field() ?>
        <div class="row g-3">
            <div class="col-md-8">
                <label class="form-label">Título *</label>
                <input type="text" name="title" class="form-control" value="<?= e(old('title', $unit->title)) ?>" required>
            </div>
            <div class="col-md-2">
                <label class="form-label">Orden</label>
                <input type="number" name="position" class="form-control" value="<?= e(old('position', $unit->position)) ?>">
            </div>
            <div class="col-md-2">
                <label class="form-label">Estado</label>
                <select name="status" class="form-select">
                    <option value="DRAFT" <?= selected(old('status', $unit->status), 'DRAFT') ?>>Borrador</option>
                    <option value="PUBLISHED" <?= selected(old('status', $unit->status), 'PUBLISHED') ?>>Publicada</option>
                    <option value="UNPUBLISHED" <?= selected(old('status', $unit->status), 'UNPUBLISHED') ?>>Despublicada</option>
                </select>
            </div>
            <div class="col-12">
                <label class="form-label">Descripción</label>
                <textarea name="description" class="form-control" rows="2"><?= e(old('description', $unit->description)) ?></textarea>
            </div>
            <div class="col-12">
                <button class="btn btn-primary"><?= $isEdit ? 'Actualizar' : 'Guardar' ?></button>
            </div>
        </div>
    </form>
</div></div>
