<?php
/** @var \App\Modules\Sabers\Models\Saber $saber @var array $tracks */
$isEdit = $saber->id !== null;
?>
<div class="d-flex justify-content-between align-items-center mb-3">
    <h1 class="h4 mb-0"><?= $isEdit ? 'Editar Saber' : 'Nuevo Saber' ?></h1>
    <a href="<?= url('/sabers') ?>" class="btn btn-outline-secondary btn-sm">Volver</a>
</div>
<div class="card card-av"><div class="card-body">
    <form method="post" action="<?= $isEdit ? url('/sabers/' . $saber->id) : url('/sabers') ?>">
        <?= csrf_field() ?>
        <div class="row g-3">
            <div class="col-md-4">
                <label class="form-label">Código *</label>
                <input type="text" name="code" class="form-control" value="<?= e(old('code', $saber->code)) ?>" required>
            </div>
            <div class="col-md-4">
                <label class="form-label">Nombre *</label>
                <input type="text" name="name" class="form-control" value="<?= e(old('name', $saber->name)) ?>" required>
            </div>
            <div class="col-md-4">
                <label class="form-label">Trayecto *</label>
                <select name="track_id" class="form-select" required>
                    <option value="">Seleccione…</option>
                    <?php foreach ($tracks as $t): ?>
                        <option value="<?= (int) $t['id'] ?>" <?= selected(old('track_id', $saber->track_id), (int) $t['id']) ?>><?= e($t['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-4">
                <label class="form-label">Modo de desbloqueo</label>
                <select name="unlock_mode" class="form-select">
                    <option value="FREE" <?= selected(old('unlock_mode', $saber->unlock_mode), 'FREE') ?>>Libre</option>
                    <option value="SEQUENTIAL" <?= selected(old('unlock_mode', $saber->unlock_mode), 'SEQUENTIAL') ?>>Secuencial</option>
                </select>
            </div>
            <div class="col-md-4">
                <label class="form-label">Estado</label>
                <select name="status" class="form-select">
                    <option value="ACTIVE" <?= selected(old('status', $saber->status), 'ACTIVE') ?>>Activo</option>
                    <option value="INACTIVE" <?= selected(old('status', $saber->status), 'INACTIVE') ?>>Inactivo</option>
                </select>
            </div>
            <div class="col-12">
                <label class="form-label">Descripción</label>
                <textarea name="description" class="form-control" rows="2"><?= e(old('description', $saber->description)) ?></textarea>
            </div>
            <div class="col-12">
                <button class="btn btn-primary"><?= $isEdit ? 'Actualizar' : 'Guardar' ?></button>
            </div>
        </div>
    </form>
</div></div>
