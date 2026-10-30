<?php
/** @var \App\Modules\Sabers\Models\SaberAssignment $assignment @var array $teachers @var array $components @var array $students @var array $evaluableBlocks */
$componentByBlock = [];
foreach ($components as $c) {
    if (! empty($c['block_id'])) {
        $componentByBlock[(int) $c['block_id']] = $c;
    }
}
$to20 = fn ($pct) => number_format(((float) $pct) / 5, 2, '.', '');
?>
<div class="d-flex justify-content-between align-items-center mb-3">
    <h1 class="h4 mb-0"><?= e($assignment->saber_name) ?> — <?= e($assignment->sectionLabel()) ?></h1>
    <a href="<?= url('/saber-assignments') ?>" class="btn btn-outline-secondary btn-sm">Volver</a>
</div>

<div class="card card-av mb-3">
    <div class="card-body">
        <dl class="row mb-0">
            <dt class="col-sm-3">Saber</dt><dd class="col-sm-9"><?= e($assignment->saber_name) ?></dd>
            <dt class="col-sm-3">Sección</dt><dd class="col-sm-9"><?= e($assignment->sectionLabel()) ?> (<?= e($assignment->track_name) ?>)</dd>
            <dt class="col-sm-3">Período</dt><dd class="col-sm-9"><?= e($assignment->period_name) ?></dd>
            <dt class="col-sm-3">Vigencia</dt><dd class="col-sm-9"><?= e($assignment->start_date ?? '—') ?> a <?= e($assignment->end_date ?? '—') ?></dd>
            <dt class="col-sm-3">Estado</dt><dd class="col-sm-9"><span class="badge <?= $assignment->status === 'PUBLISHED' ? 'bg-success' : 'bg-secondary' ?>"><?= e($assignment->status) ?></span></dd>
            <dt class="col-sm-3">Docentes</dt>
            <dd class="col-sm-9">
                <?php foreach ($teachers as $t): ?>
                    <span class="badge bg-info text-dark"><?= e($t['last_name'] . ', ' . $t['first_name']) ?><?= ! empty($t['is_primary']) ? ' (principal)' : '' ?></span>
                <?php endforeach; ?>
                <?php if (! $teachers): ?><span class="text-muted">Sin docentes asignados.</span><?php endif; ?>
            </dd>
        </dl>
    </div>
</div>

<div class="row g-3">
    <div class="col-lg-5">
        <div class="card card-av mb-3">
            <div class="card-body">
                <h2 class="h6">Configuración de calificaciones</h2>
                <p class="small text-muted">Asigne un peso (%) a cada contenido H5P evaluable del Saber. La nota final se calcula con el promedio ponderado y se expresa sobre 20 (aprueba con 12).</p>
                <?php if (! $evaluableBlocks): ?>
                    <div class="alert alert-info small mb-0">Este Saber aún no tiene contenido H5P evaluable.</div>
                <?php else: ?>
                <form method="post" action="<?= url('/saber-assignments/' . $assignment->id . '/components') ?>">
                    <?= csrf_field() ?>
                    <div id="components">
                        <?php foreach ($evaluableBlocks as $i => $b): ?>
                            <?php $prev = $componentByBlock[(int) $b['id']] ?? null; ?>
                            <div class="row g-2 mb-2 align-items-center">
                                <div class="col-8">
                                    <input type="hidden" name="components[<?= $i ?>][block_id]" value="<?= (int) $b['id'] ?>">
                                    <input type="hidden" name="components[<?= $i ?>][source_type]" value="H5P">
                                    <input type="hidden" name="components[<?= $i ?>][name]" value="<?= e($b['title'] ?: 'H5P') ?>">
                                    <span class="small"><?= e($b['title'] ?: '(sin título)') ?> <span class="text-muted">· <?= e($b['unit_title']) ?></span></span>
                                </div>
                                <div class="col-4">
                                    <div class="input-group input-group-sm">
                                        <input type="number" step="0.01" min="0" name="components[<?= $i ?>][weight]" class="form-control" value="<?= e($prev['weight'] ?? '') ?>" placeholder="%">
                                        <span class="input-group-text">%</span>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <button class="btn btn-sm btn-primary">Guardar pesos</button>
                </form>
                <?php endif; ?>
            </div>
        </div>

        <div class="card card-av">
            <div class="card-body">
                <h2 class="h6">Evaluaciones H5P</h2>
                <?php if (! $evaluableBlocks): ?>
                    <p class="text-muted small mb-0">No hay evaluaciones H5P para este Saber.</p>
                <?php else: ?>
                    <ul class="list-unstyled mb-0">
                        <?php foreach ($evaluableBlocks as $b): ?>
                            <li class="d-flex justify-content-between align-items-center py-1">
                                <span class="small"><?= e($b['title'] ?: '(sin título)') ?> <span class="text-muted">· <?= e($b['unit_title']) ?></span></span>
                                <a href="<?= url('/saber-assignments/' . $assignment->id . '/h5p/' . (int) $b['id'] . '/submissions') ?>" class="btn btn-sm btn-outline-primary">Corregir / Liberar</a>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <div class="col-lg-7">
        <div class="card card-av">
            <div class="card-body">
                <h2 class="h6">Estudiantes: progreso y calificación</h2>
                <div class="table-responsive">
                    <table class="table table-sm align-middle mb-0">
                        <thead class="table-light"><tr><th>Estudiante</th><th>Progreso</th><th>Nota (20)</th></tr></thead>
                        <tbody>
                        <?php foreach ($students as $st): ?>
                            <tr>
                                <td><?= e($st['last_name'] . ', ' . $st['first_name']) ?></td>
                                <td>
                                    <div class="progress progress-av" style="width:120px">
                                        <div class="progress-bar" style="width: <?= (float) $st['progress'] ?>%"></div>
                                    </div>
                                    <?= (int) round($st['progress']) ?>%
                                </td>
                                <td>
                                    <?php if ($st['grade_value'] !== null): ?>
                                        <strong><?= e($to20($st['grade_value'])) ?></strong>
                                        <span class="badge <?= (float) $to20($st['grade_value']) >= 12 ? 'bg-success' : 'bg-danger' ?> ms-1">
                                            <?= (float) $to20($st['grade_value']) >= 12 ? 'Aprobado' : 'Reprobado' ?>
                                        </span>
                                    <?php else: ?>—<?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        <?php if (! $students): ?><tr><td colspan="3" class="text-muted">Sin estudiantes.</td></tr><?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
