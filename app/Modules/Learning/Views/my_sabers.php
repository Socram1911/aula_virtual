<?php /** @var array $items @var string $firstName */ ?>
<div class="welcome-band mb-4">
    <h1><?= $firstName !== '' ? 'Hola, ' . e($firstName) : 'Tu aprendizaje' ?></h1>
    <p class="mb-0">Consulta tus saberes, avanza en las unidades y sigue tu progreso.</p>
</div>

<h2 class="h5 mb-3">Mis Saberes</h2>
<?php if (! $items): ?>
    <div class="alert alert-info">No tiene saberes asignados todavía.</div>
<?php else: ?>
    <div class="row g-3">
        <?php foreach ($items as $a): ?>
            <div class="col-md-6 col-lg-4">
                <div class="card card-av h-100">
                    <div class="card-body">
                        <h2 class="h6 mb-1"><?= e($a->saber_name) ?></h2>
                        <p class="text-muted small mb-2"><?= e('Sección ' . $a->section_number) ?> · <?= e($a->period_name) ?></p>
                        <div class="progress progress-av mb-1">
                            <div class="progress-bar" style="width: <?= (float) $a->progress ?>%"><?= (int) round($a->progress) ?>%</div>
                        </div>
                        <a href="<?= url('/sabers/' . $a->saber_id) ?>" class="btn btn-sm btn-primary mt-2">Ver unidades</a>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>
