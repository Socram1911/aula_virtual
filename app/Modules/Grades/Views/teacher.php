<?php /** @var array $rows */ ?>
<h1 class="h4 mb-3">Calificaciones (compuestas)</h1>
<div class="card card-av">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr><th>Estudiante</th><th>Saber</th><th>Sección</th><th>Porcentaje</th><th>Nota</th></tr>
            </thead>
            <tbody>
            <?php foreach ($rows as $r): ?>
                <?php $nota20 = number_format(((float) $r['percentage']) / 5, 2, '.', ''); ?>
                <tr>
                    <td><?= e($r['last_name'] . ', ' . $r['first_name']) ?></td>
                    <td><?= e($r['saber_name']) ?></td>
                    <td><?= e('Sección ' . $r['section_number']) ?></td>
                    <td><span class="badge bg-primary"><?= e($r['percentage']) ?>%</span></td>
                    <td>
                        <strong><?= e($nota20) ?></strong>/20
                        <span class="badge <?= (float) $nota20 >= 12 ? 'bg-success' : 'bg-danger' ?> ms-1">
                            <?= (float) $nota20 >= 12 ? 'Aprobado' : 'Reprobado' ?>
                        </span>
                    </td>
                </tr>
            <?php endforeach; ?>
            <?php if (! $rows): ?><tr><td colspan="5" class="text-center text-muted py-4">No hay calificaciones.</td></tr><?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
