<?php
/** @var array $studentsPerSection @var array $studentsPerTrack @var array $sabersPerTrack @var array $teachersPerSaber @var array $academicResults @var array $evaluationResults */
?>
<h1 class="h4 mb-3">Reportes</h1>

<div class="row g-3">
    <div class="col-md-6">
        <div class="card card-av"><div class="card-body">
            <h2 class="h6">Estudiantes por sección</h2>
            <table class="table table-sm mb-0"><thead><tr><th>Sección</th><th>Período</th><th>Estudiantes</th></tr></thead><tbody>
            <?php foreach ($studentsPerSection as $r): ?>
                <tr><td>Sección <?= e($r['number']) ?></td><td><?= e($r['period_name']) ?></td><td><?= (int) $r['total'] ?></td></tr>
            <?php endforeach; ?>
            <?php if (! $studentsPerSection): ?><tr><td colspan="3" class="text-muted">Sin datos.</td></tr><?php endif; ?>
            </tbody></table>
        </div></div>
    </div>
    <div class="col-md-6">
        <div class="card card-av"><div class="card-body">
            <h2 class="h6">Estudiantes por trayecto</h2>
            <table class="table table-sm mb-0"><thead><tr><th>Trayecto</th><th>Estudiantes</th></tr></thead><tbody>
            <?php foreach ($studentsPerTrack as $r): ?>
                <tr><td><?= e($r['track_name']) ?></td><td><?= (int) $r['total'] ?></td></tr>
            <?php endforeach; ?>
            <?php if (! $studentsPerTrack): ?><tr><td colspan="2" class="text-muted">Sin datos.</td></tr><?php endif; ?>
            </tbody></table>
        </div></div>
    </div>
    <div class="col-md-6">
        <div class="card card-av"><div class="card-body">
            <h2 class="h6">Saberes por trayecto</h2>
            <table class="table table-sm mb-0"><thead><tr><th>Trayecto</th><th>Saberes</th></tr></thead><tbody>
            <?php foreach ($sabersPerTrack as $r): ?>
                <tr><td><?= e($r['track_name']) ?></td><td><?= (int) $r['total'] ?></td></tr>
            <?php endforeach; ?>
            </tbody></table>
        </div></div>
    </div>
    <div class="col-md-6">
        <div class="card card-av"><div class="card-body">
            <h2 class="h6">Docentes por Saber</h2>
            <table class="table table-sm mb-0"><thead><tr><th>Saber</th><th>Docentes</th></tr></thead><tbody>
            <?php foreach ($teachersPerSaber as $r): ?>
                <tr><td><?= e($r['saber_name']) ?></td><td><?= (int) $r['total'] ?></td></tr>
            <?php endforeach; ?>
            <?php if (! $teachersPerSaber): ?><tr><td colspan="2" class="text-muted">Sin datos.</td></tr><?php endif; ?>
            </tbody></table>
        </div></div>
    </div>
    <div class="col-12">
        <div class="card card-av"><div class="card-body">
            <h2 class="h6">Resultados académicos</h2>
            <table class="table table-sm mb-0"><thead><tr><th>Estudiante</th><th>Saber</th><th>Porcentaje</th><th>Nota</th></tr></thead><tbody>
            <?php foreach ($academicResults as $r): ?>
                <tr><td><?= e($r['last_name'] . ', ' . $r['first_name']) ?></td><td><?= e($r['saber_name']) ?></td><td><?= e($r['percentage']) ?>%</td><td><?= e($r['grade_value']) ?></td></tr>
            <?php endforeach; ?>
            <?php if (! $academicResults): ?><tr><td colspan="4" class="text-muted">Sin datos.</td></tr><?php endif; ?>
            </tbody></table>
        </div></div>
    </div>
    <div class="col-12">
        <div class="card card-av"><div class="card-body">
            <h2 class="h6">Resultados de evaluaciones</h2>
            <table class="table table-sm mb-0"><thead><tr><th>Evaluación</th><th>Estudiante</th><th>Puntaje</th><th>Porcentaje</th><th>Estado</th></tr></thead><tbody>
            <?php foreach ($evaluationResults as $r): ?>
                <tr><td><?= e($r['title']) ?></td><td><?= e($r['last_name'] . ', ' . $r['first_name']) ?></td><td><?= e($r['score']) ?></td><td><?= e($r['percentage']) ?>%</td><td><?= e($r['status']) ?></td></tr>
            <?php endforeach; ?>
            <?php if (! $evaluationResults): ?><tr><td colspan="5" class="text-muted">Sin datos.</td></tr><?php endif; ?>
            </tbody></table>
        </div></div>
    </div>
</div>
