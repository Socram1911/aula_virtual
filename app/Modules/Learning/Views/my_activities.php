<?php /** @var array $assessments */ ?>
<h1 class="h4 mb-3">Actividades y Evaluaciones</h1>
<?php if (! $assessments): ?>
    <div class="alert alert-info">No hay evaluaciones disponibles para sus secciones.</div>
<?php else: ?>
    <div class="row g-3">
        <?php foreach ($assessments as $a): ?>
            <div class="col-md-6 col-lg-4">
                <div class="card card-av h-100">
                    <div class="card-body">
                        <h2 class="h6 mb-1"><?= e($a['title']) ?></h2>
                        <p class="text-muted small mb-2">Puntaje máx: <?= e($a['max_score']) ?> · Aprueba con <?= e($a['passing_score']) ?></p>
                        <a href="<?= url('/assessments/' . $a['id'] . '/take') ?>" class="btn btn-sm btn-primary">Presentar evaluación</a>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>
