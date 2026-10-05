<?php /** @var array $tracks */ ?>
<div class="d-flex justify-content-between align-items-center mb-3">
    <h1 class="h4 mb-0">Trayectos</h1>
    <a href="<?= url('/tracks/create') ?>" class="btn btn-primary btn-sm">+ Nuevo trayecto</a>
</div>
<div class="card card-av">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr><th>Orden</th><th>Código</th><th>Nombre</th><th>Estado</th><th class="text-end">Acciones</th></tr>
            </thead>
            <tbody>
            <?php foreach ($tracks as $t): ?>
                <tr>
                    <td><?= e($t->position) ?></td>
                    <td><?= e($t->code) ?></td>
                    <td><?= e($t->name) ?></td>
                    <td><span class="badge <?= $t->status === 'ACTIVE' ? 'bg-success' : 'bg-secondary' ?>"><?= e($t->status) ?></span></td>
                    <td class="text-end">
                        <a href="<?= url('/tracks/' . $t->id . '/edit') ?>" class="btn btn-sm btn-outline-secondary">Editar</a>
                        <form method="post" action="<?= url('/tracks/' . $t->id . '/delete') ?>" class="d-inline" onsubmit="return confirm('¿Eliminar este trayecto?');">
                            <?= csrf_field() ?>
                            <button class="btn btn-sm btn-outline-danger">Eliminar</button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
            <?php if (! $tracks): ?><tr><td colspan="5" class="text-center text-muted py-4">No hay trayectos registrados.</td></tr><?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
