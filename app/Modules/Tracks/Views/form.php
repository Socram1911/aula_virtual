<?php
/** @var \App\Modules\Tracks\Models\Track $track @var array $periods @var array $selected */
$isEdit = $track->id !== null;
?>
<div class="d-flex justify-content-between align-items-center mb-3">
    <h1 class="h4 mb-0"><?= $isEdit ? 'Editar Trayecto' : 'Nuevo Trayecto' ?></h1>
    <a href="<?= url('/tracks') ?>" class="btn btn-outline-secondary btn-sm">Volver</a>
</div>
<div class="card card-av"><div class="card-body">
    <form method="post" action="<?= $isEdit ? url('/tracks/' . $track->id) : url('/tracks') ?>">
        <?= csrf_field() ?>
        <div class="row g-3">
            <div class="col-md-3">
                <label class="form-label">Código *</label>
                <input type="text" name="code" class="form-control" value="<?= e(old('code', $track->code)) ?>" required>
            </div>
            <div class="col-md-5">
                <label class="form-label">Nombre *</label>
                <input type="text" name="name" class="form-control" value="<?= e(old('name', $track->name)) ?>" required>
            </div>
            <div class="col-md-2">
                <label class="form-label">Orden</label>
                <input type="number" name="position" class="form-control" value="<?= e(old('position', $track->position)) ?>">
            </div>
            <div class="col-md-2">
                <label class="form-label">Estado</label>
                <select name="status" class="form-select">
                    <option value="ACTIVE" <?= selected(old('status', $track->status), 'ACTIVE') ?>>Activo</option>
                    <option value="INACTIVE" <?= selected(old('status', $track->status), 'INACTIVE') ?>>Inactivo</option>
                </select>
            </div>
            <div class="col-12">
                <label class="form-label">Descripción</label>
                <textarea name="description" class="form-control" rows="2"><?= e(old('description', $track->description)) ?></textarea>
            </div>
            <div class="col-12">
                <label class="form-label">Períodos asociados</label>
                <div class="row">
                    <?php foreach ($periods as $p): ?>
                        <div class="col-md-4 form-check">
                            <input class="form-check-input" type="checkbox" name="period_ids[]" value="<?= (int) $p['id'] ?>"
                                   id="p<?= (int) $p['id'] ?>"
                                   <?= in_array((int) $p['id'], array_map('intval', old('period_ids', $selected)), true) ? 'checked' : '' ?>>
                            <label class="form-check-label" for="p<?= (int) $p['id'] ?>"><?= e($p['name']) ?></label>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <div class="col-12">
                <button class="btn btn-primary"><?= $isEdit ? 'Actualizar' : 'Guardar' ?></button>
            </div>
        </div>
    </form>
</div></div>
