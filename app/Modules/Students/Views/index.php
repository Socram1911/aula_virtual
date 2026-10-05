<?php /** @var array $students */ ?>
<div class="d-flex justify-content-between align-items-center mb-3">
    <h1 class="h4 mb-0">Estudiantes</h1>
</div>
<div class="card card-av">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr><th>Nombre</th><th>Correo</th><th>Secciones activas</th><th>Estado</th></tr>
            </thead>
            <tbody>
            <?php foreach ($students as $s): ?>
                <tr>
                    <td><?= e($s['last_name'] . ', ' . $s['first_name']) ?></td>
                    <td><?= e($s['email']) ?></td>
                    <td><span class="badge bg-primary"><?= (int) $s['enrollments'] ?></span></td>
                    <td><span class="badge <?= $s['status'] === 'ACTIVE' ? 'bg-success' : 'bg-secondary' ?>"><?= e($s['status']) ?></span></td>
                </tr>
            <?php endforeach; ?>
            <?php if (! $students): ?><tr><td colspan="4" class="text-center text-muted py-4">No hay estudiantes registrados.</td></tr><?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
