<?php /** @var array $roles @var string $firstName */ ?>
<div class="welcome-band mb-4">
    <h1>Bienvenido<?= $firstName !== '' ? ', ' . e($firstName) : '' ?></h1>
    <p class="mb-0">Gestiona la estructura académica de tu institución: períodos, trayectos, secciones, saberes, usuarios y reportes.</p>
</div>

<div class="card card-av">
    <div class="card-body">
        <p class="mb-0 text-muted">Usa el menú lateral para acceder a los módulos según tu rol
            (<?= e(implode(', ', $roles)) ?>).</p>
    </div>
</div>
