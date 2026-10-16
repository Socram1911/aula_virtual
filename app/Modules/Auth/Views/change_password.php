<div class="row justify-content-center">
    <div class="col-md-6 col-lg-5">
        <div class="card card-av">
            <div class="card-body p-4">
                <h1 class="h4 mb-1">Cambiar contraseña</h1>
                <p class="text-muted mb-4">Actualiza tu contraseña de acceso.</p>
                <form method="post" action="<?= url('/account/password') ?>" id="change-password-form" novalidate>
                    <?= csrf_field() ?>
                    <div class="mb-3">
                        <label for="current_password" class="form-label">Contraseña actual</label>
                        <input type="password" class="form-control" id="current_password" name="current_password" required autocomplete="current-password">
                    </div>
                    <div class="mb-3">
                        <label for="new_password" class="form-label">Nueva contraseña</label>
                        <input type="password" class="form-control" id="new_password" name="new_password" required
                               autocomplete="new-password" minlength="8">
                        <div class="form-text">Mínimo 8 caracteres.</div>
                    </div>
                    <div class="mb-3">
                        <label for="confirm_password" class="form-label">Confirmar nueva contraseña</label>
                        <input type="password" class="form-control" id="confirm_password" name="confirm_password" required autocomplete="new-password">
                    </div>
                    <button type="submit" class="btn btn-primary">Actualizar contraseña</button>
                </form>
            </div>
        </div>
    </div>
</div>
