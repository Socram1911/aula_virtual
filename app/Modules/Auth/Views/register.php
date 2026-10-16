<div class="row justify-content-center">
    <div class="col-md-5 col-lg-4">
        <div class="card card-av">
            <div class="card-body p-4">
                <h1 class="login-brand mb-1 text-center">Aula Virtual Académica</h1>
                <p class="text-muted text-center mb-4">Solicita tu cuenta</p>
                <form method="post" action="<?= url('/register') ?>" id="register-form" novalidate>
                    <?= csrf_field() ?>
                    <div class="mb-3">
                        <label for="first_name" class="form-label">Nombre</label>
                        <input type="text" class="form-control" id="first_name" name="first_name"
                               value="<?= e(old('first_name')) ?>" required autofocus>
                    </div>
                    <div class="mb-3">
                        <label for="last_name" class="form-label">Apellido</label>
                        <input type="text" class="form-control" id="last_name" name="last_name"
                               value="<?= e(old('last_name')) ?>" required>
                    </div>
                    <div class="mb-3">
                        <label for="email" class="form-label">Correo electrónico</label>
                        <input type="email" class="form-control" id="email" name="email"
                               value="<?= e(old('email')) ?>" required>
                    </div>
                    <button type="submit" class="btn btn-primary w-100">Enviar solicitud</button>
                </form>
                <div class="text-center mt-3">
                    <a href="<?= url('/login') ?>" class="small">¿Ya tienes cuenta? Inicia sesión</a>
                </div>
            </div>
        </div>
        <p class="text-muted small mt-3 text-center">Al enviar tu solicitud, un administrador revisará tu cuenta,
            te asignará un tipo de cuenta (estudiante, docente o administrador) y recibirás tu contraseña por correo.</p>
    </div>
</div>
