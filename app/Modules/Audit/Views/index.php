<?php /** @var array $logs */ ?>
<h1 class="h4 mb-3">Auditoría</h1>
<div class="card card-av">
    <div class="table-responsive">
        <table class="table table-sm table-hover align-middle mb-0">
            <thead class="table-light">
                <tr><th>Fecha</th><th>Usuario</th><th>Acción</th><th>Entidad</th><th>ID</th><th>IP</th></tr>
            </thead>
            <tbody>
            <?php foreach ($logs as $l): ?>
                <tr>
                    <td><?= e($l['created_at']) ?></td>
                    <td><?= e(trim(($l['first_name'] ?? '') . ' ' . ($l['last_name'] ?? '')) ?: 'Sistema') ?></td>
                    <td><?= e($l['action']) ?></td>
                    <td><?= e($l['entity_type']) ?></td>
                    <td><?= (int) $l['entity_id'] ?></td>
                    <td><?= e($l['ip_address'] ?? '') ?></td>
                </tr>
            <?php endforeach; ?>
            <?php if (! $logs): ?><tr><td colspan="6" class="text-center text-muted py-4">Sin registros de auditoría.</td></tr><?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
