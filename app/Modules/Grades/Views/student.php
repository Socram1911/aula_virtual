<?php /** @var array $grades */ ?>
<h1 class="h4 mb-3">Calificaciones</h1>
<div class="card card-av">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr><th>Saber</th><th>Sección</th><th>Fuente</th><th>Puntaje</th><th>Porcentaje</th><th>Fecha</th></tr>
            </thead>
            <tbody>
            <?php foreach ($grades as $g): ?>
                <tr>
                    <td><?= e($g['saber_name']) ?></td>
                    <td><?= e('Sección ' . $g['section_number']) ?></td>
                    <td><?= e($g['source_type']) ?></td>
                    <td><?= e($g['score']) ?></td>
                    <td><span class="badge bg-primary"><?= e($g['percentage']) ?>%</span></td>
                    <td><?= e($g['graded_at']) ?></td>
                </tr>
            <?php endforeach; ?>
            <?php if (! $grades): ?><tr><td colspan="6" class="text-center text-muted py-4">No hay calificaciones registradas.</td></tr><?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
