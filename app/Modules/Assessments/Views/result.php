<?php
/** @var \App\Modules\Assessments\Models\Assessment $assessment @var array $result */
$passed = ! empty($result['passed']);
?>
<div class="row justify-content-center">
    <div class="col-md-6">
        <div class="card card-av text-center">
            <div class="card-body py-5">
                <div class="display-1 mb-2"><?= $passed ? '🎉' : '📝' ?></div>
                <h1 class="h4"><?= e($assessment->title) ?></h1>
                <div class="display-4 fw-bold <?= $passed ? 'text-success' : 'text-danger' ?>"><?= e($result['percentage'] ?? 0) ?>%</div>
                <p class="mb-1">Puntaje: <?= e($result['score'] ?? 0) ?> de <?= e($result['max_score'] ?? $assessment->max_score) ?></p>
                <span class="badge <?= $passed ? 'bg-success' : 'bg-danger' ?> fs-6"><?= $passed ? 'Aprobado' : 'No aprobado' ?></span>
                <div class="mt-4">
                    <a href="<?= url('/my-sabers') ?>" class="btn btn-primary">Volver a Mis Saberes</a>
                    <a href="<?= url('/assessments/' . $assessment->id . '/take') ?>" class="btn btn-outline-secondary">Reintentar</a>
                </div>
            </div>
        </div>
    </div>
</div>
