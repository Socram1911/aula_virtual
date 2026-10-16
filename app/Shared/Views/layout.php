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
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#nav">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="nav">
            <ul class="navbar-nav ms-auto align-items-lg-center">
                <li class="nav-item"><span class="navbar-text me-3"><?= e($user['first_name'] . ' ' . $user['last_name']) ?></span></li>
                <li class="nav-item">
                    <form method="post" action="<?= url('/logout') ?>" class="d-inline">
                        <?= csrf_field() ?>
                        <button class="btn btn-outline-light btn-sm">Cerrar sesión</button>
                    </form>
                </li>
            </ul>
        </div>
    </div>
</nav>
<?php endif; ?>

<div class="container-fluid">
    <div class="row">
        <?php if ($user): ?>
        <div class="col-lg-2 d-none d-lg-block">
            <div class="card card-av p-2 sticky-top" style="top:1rem">
                <div class="d-grid gap-1">
                    <?php if ($isStudent): ?>
                        <a class="sidebar-link<?= ($_SERVER['REQUEST_URI'] ?? '') === url('/my-sabers') ? ' active' : '' ?>" href="<?= url('/my-sabers') ?>">Inicio</a>
                        <a class="sidebar-link" href="<?= url('/my-section') ?>">Mi Sección</a>
                        <a class="sidebar-link" href="<?= url('/my-activities') ?>">Actividades</a>
                        <a class="sidebar-link" href="<?= url('/grades') ?>">Calificaciones</a>
                    <?php endif; ?>
                    <?php if ($isTeacher && ! $isAdmin): ?>
                        <a class="sidebar-link<?= ($_SERVER['REQUEST_URI'] ?? '') === url('/dashboard') ? ' active' : '' ?>" href="<?= url('/dashboard') ?>">Mis Saberes</a>
                        <a class="sidebar-link" href="<?= url('/sections') ?>">Secciones</a>
                        <a class="sidebar-link" href="<?= url('/saber-assignments') ?>">Asignaciones</a>
                        <a class="sidebar-link" href="<?= url('/grades') ?>">Calificaciones</a>
                        <a class="sidebar-link" href="<?= url('/reports') ?>">Reportes</a>
                    <?php endif; ?>
                    <?php if ($isAdmin): ?>
                        <a class="sidebar-link<?= ($_SERVER['REQUEST_URI'] ?? '') === url('/dashboard') ? ' active' : '' ?>" href="<?= url('/dashboard') ?>">Dashboard</a>
                        <a class="sidebar-link" href="<?= url('/sections') ?>">Secciones</a>
                        <a class="sidebar-link" href="<?= url('/saber-assignments') ?>">Asignaciones</a>
                        <hr class="my-1">
                        <a class="sidebar-link" href="<?= url('/users') ?>">Usuarios</a>
                        <a class="sidebar-link" href="<?= url('/students') ?>">Estudiantes</a>
                        <a class="sidebar-link" href="<?= url('/academic-periods') ?>">Períodos</a>
                        <a class="sidebar-link" href="<?= url('/tracks') ?>">Trayectos</a>
                        <a class="sidebar-link" href="<?= url('/sabers') ?>">Saberes</a>
                        <a class="sidebar-link" href="<?= url('/reports') ?>">Reportes</a>
                        <a class="sidebar-link" href="<?= url('/audit') ?>">Auditoría</a>
                        <a class="sidebar-link" href="<?= url('/grades') ?>">Calificaciones</a>
                    <?php endif; ?>
                    <hr class="my-1">
                    <a class="sidebar-link<?= ($_SERVER['REQUEST_URI'] ?? '') === url('/account/password') ? ' active' : '' ?>" href="<?= url('/account/password') ?>">Cambiar contraseña</a>
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
</body>
</html>
