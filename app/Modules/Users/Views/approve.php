<?php
/** @var \App\Modules\Users\Models\User $user @var array $roles */
?>
<div class="d-flex justify-content-between align-items-center mb-3">
    <h1 class="h4 mb-0">Aprobar cuenta</h1>
    <a href="<?= url('/users') ?>" class="btn btn-outline-secondary btn-sm">Volver</a>
</div>

<div class="card card-av mb-3">
    <div class="card-body">
        <dl class="row mb-0">
            <dt class="col-sm-3">Nombre</dt><dd class="col-sm-9"><?= e($user->fullName()) ?></dd>
            <dt class="col-sm-3">Correo</dt><dd class="col-sm-9"><?= e($user->email) ?></dd>
            <dt class="col-sm-3">Estado</dt><dd class="col-sm-9"><span class="badge bg-warning text-dark">Pendiente</span></dd>
        </dl>
    </div>
</div>

<div class="card card-av">
    <div class="card-body">
        <p class="text-muted mb-3">Selecciona el tipo de cuenta que tendrá este usuario. Al aprobar se generará una
            <strong>contraseña aleatoria</strong> que se enviará por correo electrónico.</p>
        <form method="post" action="<?= url('/users/' . $user->id . '/approve') ?>" id="approve-form">
            <?= csrf_field() ?>
            <div class="row">
                <?php foreach ($roles as $r): ?>
                    <div class="col-md-4 form-check">
                        <input class="form-check-input" type="checkbox" name="roles[]" value="<?= e($r) ?>"
                               id="role-<?= e($r) ?>" <?= in_array($r, (array) old('roles', []), true) ? 'checked' : '' ?>>
                        <label class="form-check-label" for="role-<?= e($r) ?>">
                            <?= match ($r) {
                                'ADMIN' => 'Administrador',
                                'TEACHER' => 'Docente',
                                'STUDENT' => 'Estudiante',
                                default => $r,
                            } ?>
                        </label>
                    </div>
                <?php endforeach; ?>
            </div>
            <button class="btn btn-success mt-3">Aprobar y enviar contraseña</button>
        </form>
    </div>
</div>
