<?php
/** @var \App\Modules\Sabers\Models\SaberAssignment $assignment @var array $teachers @var array $components @var array $students */
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
        <div class="card card-av">
            <div class="card-body">
                <h2 class="h6">Configuración de calificaciones</h2>
                <form method="post" action="<?= url('/saber-assignments/' . $assignment->id . '/components') ?>">
                    <?= csrf_field() ?>
                    <p class="small text-muted">Defina componentes con pesos que sumen 100%. Ej.: Actividades 20%, Evaluaciones 60%, Proyecto 20%.</p>
                    <div id="components">
                        <?php foreach ($components as $i => $c): ?>
                            <div class="row g-2 mb-2">
                                <div class="col-4"><input type="text" name="components[<?= $i ?>][name]" class="form-control form-control-sm" value="<?= e($c['name']) ?>" placeholder="Nombre"></div>
                                <div class="col-4">
                                    <select name="components[<?= $i ?>][source_type]" class="form-select form-select-sm">
                                        <option value="ACTIVITIES" <?= selected($c['source_type'], 'ACTIVITIES') ?>>Actividades</option>
                                        <option value="ASSESSMENTS" <?= selected($c['source_type'], 'ASSESSMENTS') ?>>Evaluaciones</option>
                                        <option value="PROJECT" <?= selected($c['source_type'], 'PROJECT') ?>>Proyecto</option>
                                    </select>
                                </div>
                                <div class="col-4"><input type="number" step="0.01" name="components[<?= $i ?>][weight]" class="form-control form-control-sm" value="<?= e($c['weight']) ?>" placeholder="%"></div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <div class="d-flex gap-2">
                        <button type="button" class="btn btn-sm btn-outline-secondary" id="add-component">+ Componente</button>
                        <button class="btn btn-sm btn-primary">Guardar</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <div class="col-lg-7">
        <div class="card card-av">
            <div class="card-body">
                <h2 class="h6">Estudiantes: progreso y calificación</h2>
                <div class="table-responsive">
                    <table class="table table-sm align-middle mb-0">
                        <thead class="table-light"><tr><th>Estudiante</th><th>Progreso</th><th>Nota</th></tr></thead>
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
                                <td><?= $st['grade_value'] !== null ? e($st['grade_value']) : '—' ?></td>
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

<script>
document.getElementById('add-component').addEventListener('click', function () {
    var container = document.getElementById('components');
    var idx = container.children.length;
    var row = document.createElement('div');
    row.className = 'row g-2 mb-2';
    row.innerHTML = '<div class="col-4"><input type="text" name="components[' + idx + '][name]" class="form-control form-control-sm" placeholder="Nombre"></div>' +
        '<div class="col-4"><select name="components[' + idx + '][source_type]" class="form-select form-select-sm">' +
        '<option value="ACTIVITIES">Actividades</option><option value="ASSESSMENTS">Evaluaciones</option><option value="PROJECT">Proyecto</option></select></div>' +
        '<div class="col-4"><input type="number" step="0.01" name="components[' + idx + '][weight]" class="form-control form-control-sm" placeholder="%"></div>';
    container.appendChild(row);
});
</script>
