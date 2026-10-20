<div class="login-page">
    <div class="row g-0 min-vh-100">
        <div class="col-12 col-lg-5 d-flex align-items-center">
            <div class="login-form-wrap w-100 px-3 px-sm-4 py-5">
                <div class="card card-av shadow-sm mx-auto" style="max-width: 420px;">
                    <div class="card-body p-4">
                        <h1 class="login-brand mb-1 text-center">Aula Virtual</h1>
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
        <div class="col-lg-7 d-none d-lg-block login-hero" aria-hidden="true"></div>
    </div>
</div>
