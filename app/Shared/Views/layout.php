<?php
/** @var string $content Contenido de la vista. */
use App\Core\Auth\Auth;
use App\Core\Csrf\Csrf;
use App\Core\Session\Session;

$user = Auth::user();
$title = $title ?? 'Aula Virtual Académica';
$errors = Session::pullFlash('_errors', []);
$success = Session::pullFlash('_success');
$roles = $user['roles'] ?? [];
$isAdmin = in_array('ADMIN', $roles, true);
$isTeacher = in_array('TEACHER', $roles, true);
$isStudent = in_array('STUDENT', $roles, true);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="<?= e(Csrf::token()) ?>">
    <title><?= e($title) ?></title>
    <link rel="icon" type="image/svg+xml" href="<?= asset('img/favicon.svg') ?>">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Fraunces:opsz,wght@9..144,500;9..144,600;9..144,700&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="<?= asset('css/app.css') ?>" rel="stylesheet">
</head>
<body>
<?php if ($user): ?>
<nav class="navbar navbar-expand-lg navbar-dark navbar-av mb-4">
    <div class="container-fluid">
        <a class="navbar-brand fw-bold" href="<?= url('/dashboard') ?>">Aula Virtual</a>
        <div class="d-none d-lg-flex align-items-center">
            <span class="navbar-text me-3"><?= e($user['first_name'] . ' ' . $user['last_name']) ?></span>
            <form method="post" action="<?= url('/logout') ?>" class="d-inline">
                <?= csrf_field() ?>
                <button class="btn btn-outline-light btn-sm">Cerrar sesión</button>
            </form>
        </div>
        <button class="navbar-toggler d-lg-none" type="button" data-bs-toggle="offcanvas" data-bs-target="#mobileNav" aria-controls="mobileNav" aria-label="Abrir menú">
            <span class="navbar-toggler-icon"></span>
        </button>
    </div>
</nav>

<div class="offcanvas offcanvas-start" tabindex="-1" id="mobileNav" aria-labelledby="mobileNavLabel">
    <div class="offcanvas-header navbar-av">
        <h5 class="offcanvas-title text-white" id="mobileNavLabel">Menú</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="offcanvas" aria-label="Cerrar"></button>
    </div>
    <div class="offcanvas-body">
        <div class="d-grid gap-1">
            <?php include __DIR__ . '/_sidebar_links.php'; ?>
        </div>
        <hr>
        <div class="d-flex justify-content-between align-items-center">
            <span class="text-muted small"><?= e($user['first_name'] . ' ' . $user['last_name']) ?></span>
            <form method="post" action="<?= url('/logout') ?>" class="d-inline">
                <?= csrf_field() ?>
                <button class="btn btn-outline-secondary btn-sm">Cerrar sesión</button>
            </form>
        </div>
    </div>
</div>
<?php endif; ?>

<div class="container-fluid">
    <div class="row">
        <?php if ($user): ?>
        <div class="col-lg-2 d-none d-lg-block">
            <div class="card card-av p-2 sticky-top" style="top:1rem">
                <div class="d-grid gap-1">
                    <?php include __DIR__ . '/_sidebar_links.php'; ?>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <main class="<?= $user ? 'col-lg-10' : 'col-12' ?>">
            <?php if ($errors): ?>
                <div class="alert alert-danger" role="alert">
                    <ul class="mb-0">
                        <?php foreach ($errors as $error): ?><li><?= e($error) ?></li><?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>
            <?php if ($success): ?>
                <div class="alert alert-success" role="alert" data-auto-dismiss><?= e($success) ?></div>
            <?php endif; ?>

            <?= $content ?>
        </main>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="<?= asset('h5p-standalone/main.bundle.js') ?>"></script>
<script src="<?= asset('js/app.js') ?>"></script>
<script src="<?= asset('js/auth.js') ?>"></script>
<script>
// Cierra el menú móvil al navegar a una sección.
document.addEventListener('click', function (ev) {
    var link = ev.target.closest('#mobileNav .sidebar-link');
    if (!link) { return; }
    var el = document.getElementById('mobileNav');
    var oc = bootstrap.Offcanvas.getInstance(el);
    if (oc) { oc.hide(); }
});
</script>
</body>
</html>
