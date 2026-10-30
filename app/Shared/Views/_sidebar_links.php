<?php
/**
 * Enlaces del menú lateral.
 *
 * Se incluye tanto en el sidebar de escritorio como en el offcanvas móvil.
 * Espera las variables $isStudent, $isTeacher y $isAdmin ya definidas en el layout.
 */
$req = $_SERVER['REQUEST_URI'] ?? '';
?>
<?php if ($isStudent): ?>
    <a class="sidebar-link<?= $req === url('/my-sabers') ? ' active' : '' ?>" href="<?= url('/my-sabers') ?>">Inicio</a>
    <a class="sidebar-link" href="<?= url('/my-section') ?>">Mi Sección</a>
    <a class="sidebar-link" href="<?= url('/my-activities') ?>">Actividades</a>
    <a class="sidebar-link" href="<?= url('/grades') ?>">Calificaciones</a>
<?php endif; ?>
<?php if ($isTeacher && ! $isAdmin): ?>
    <a class="sidebar-link<?= $req === url('/dashboard') ? ' active' : '' ?>" href="<?= url('/dashboard') ?>">Mis Saberes</a>
    <a class="sidebar-link" href="<?= url('/sections') ?>">Secciones</a>
    <a class="sidebar-link" href="<?= url('/saber-assignments') ?>">Asignaciones</a>
    <a class="sidebar-link" href="<?= url('/grades') ?>">Calificaciones</a>
    <a class="sidebar-link" href="<?= url('/reports') ?>">Reportes</a>
<?php endif; ?>
<?php if ($isAdmin): ?>
    <a class="sidebar-link<?= $req === url('/dashboard') ? ' active' : '' ?>" href="<?= url('/dashboard') ?>">Dashboard</a>
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
<a class="sidebar-link<?= $req === url('/account/password') ? ' active' : '' ?>" href="<?= url('/account/password') ?>">Cambiar contraseña</a>
