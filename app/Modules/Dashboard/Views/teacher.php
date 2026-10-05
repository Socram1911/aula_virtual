<?php /** @var array $assignments @var string $firstName */ ?>
<div class="welcome-band mb-4">
    <h1><?= $firstName !== '' ? 'Hola, ' . e($firstName) : 'Mis Saberes' ?></h1>
    <p class="mb-0">Aquí construyes el contenido de tus saberes asignados: unidades, actividades y evaluaciones.</p>
</div>

<div class="d-flex justify-content-between align-items-center mb-3">
    <h2 class="h5 mb-0">Mis Saberes</h2>
    <a href="<?= url('/reports') ?>" class="btn btn-outline-secondary btn-sm">Reportes</a>
</div>

<?php if (! $assignments): ?>
    <div class="alert alert-info">Aún no tiene saberes asignados. Contacte a la administración para que le asignen un Saber.</div>
<?php endif; ?>

<div class="row g-3">
    <?php foreach ($assignments as $a): ?>
        <div class="col-md-6 col-lg-4">
            <div class="card card-av h-100">
                <div class="card-body">
                    <h2 class="h6 mb-1"><?= e($a->saber_name) ?></h2>
                    <p class="text-muted small mb-2">Sección <?= e($a->section_number) ?> · <?= e($a->period_name) ?></p>
                    <span class="badge <?= $a->status === 'PUBLISHED' ? 'bg-success' : 'bg-secondary' ?> mb-2"><?= e($a->status) ?></span>
                    <div class="d-flex flex-wrap gap-1 mt-2">
                        <a href="<?= url('/sabers/' . $a->saber_id . '/units') ?>" class="btn btn-sm btn-primary">Unidades</a>
                        <a href="<?= url('/saber-assignments/' . $a->id) ?>" class="btn btn-sm btn-outline-secondary">Estudiantes</a>
                    </div>
                </div>
            </div>
        </div>
    <?php endforeach; ?>
</div>
