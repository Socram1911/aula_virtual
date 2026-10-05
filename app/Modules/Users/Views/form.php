<?php
/** @var \App\Modules\Users\Models\User $user @var array $roles */
$isEdit = $user->id !== null;
$selectedRoles = old('roles', $user->roles);
?>
<div class="d-flex justify-content-between align-items-center mb-3">
    <h1 class="h4 mb-0"><?= $isEdit ? 'Editar Usuario' : 'Nuevo Usuario' ?></h1>
    <a href="<?= url('/users') ?>" class="btn btn-outline-secondary btn-sm">Volver</a>
</div>
<div class="card card-av"><div class="card-body">
    <form method="post" action="<?= $isEdit ? url('/users/' . $user->id) : url('/users') ?>">
        <?= csrf_field() ?>
        <div class="row g-3">
            <div class="col-md-6">
                <label class="form-label">Nombre *</label>
                <input type="text" name="first_name" class="form-control" value="<?= e(old('first_name', $user->first_name)) ?>" required>
            </div>
            <div class="col-md-6">
                <label class="form-label">Apellido *</label>
                <input type="text" name="last_name" class="form-control" value="<?= e(old('last_name', $user->last_name)) ?>" required>
            </div>
            <div class="col-md-6">
                <label class="form-label">Correo electrónico *</label>
                <input type="email" name="email" class="form-control" value="<?= e(old('email', $user->email)) ?>" required>
            </div>
            <div class="col-md-6">
                <label class="form-label">Contraseña <?= $isEdit ? '(dejar en blanco para no cambiar)' : '*' ?></label>
                <input type="password" name="password" class="form-control" <?= $isEdit ? '' : 'required' ?>>
            </div>
            <div class="col-md-6">
                <label class="form-label">Estado</label>
                <select name="status" class="form-select">
                    <option value="ACTIVE" <?= selected(old('status', $user->status), 'ACTIVE') ?>>Activo</option>
                    <option value="INACTIVE" <?= selected(old('status', $user->status), 'INACTIVE') ?>>Inactivo</option>
                </select>
            </div>
            <div class="col-12">
                <label class="form-label">Roles</label>
                <div class="row">
                    <?php foreach ($roles as $r): ?>
                        <div class="col-md-3 form-check">
                            <input class="form-check-input" type="checkbox" name="roles[]" value="<?= e($r) ?>"
                                   id="role-<?= e($r) ?>" <?= in_array($r, (array) $selectedRoles, true) ? 'checked' : '' ?>>
                            <label class="form-check-label" for="role-<?= e($r) ?>"><?= e($r) ?></label>
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
