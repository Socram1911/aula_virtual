<div class="row justify-content-center">
    <div class="col-md-5 col-lg-4">
        <div class="card card-av">
            <div class="card-body p-4">
                <h1 class="login-brand mb-1 text-center">Aula Virtual Académica</h1>
                <p class="text-muted text-center mb-4">Ingrese sus credenciales</p>
                <form method="post" action="<?= url('/login') ?>">
                    <?= csrf_field() ?>
                    <div class="mb-3">
                        <label for="email" class="form-label">Correo electrónico</label>
                        <input type="email" class="form-control" id="email" name="email"
                               value="<?= e(old('email')) ?>" required autofocus>
                    </div>
                    <div class="mb-3">
                        <label for="password" class="form-label">Contraseña</label>
                        <input type="password" class="form-control" id="password" name="password" required>
                    </div>
                    <button type="submit" class="btn btn-primary w-100">Iniciar sesión</button>
                </form>
                <div class="text-center mt-3">
                    <a href="<?= url('/register') ?>" class="small">¿No tienes cuenta? Regístrate</a>
                </div>
            </div>
        </div>
    </div>
</div>
