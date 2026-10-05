<?php /** @var array $periods @var array $statuses */ ?>
<div class="d-flex justify-content-between align-items-center mb-3">
    <h1 class="h4 mb-0">Períodos Académicos</h1>
    <a href="<?= url('/academic-periods/create') ?>" class="btn btn-primary btn-sm">+ Nuevo período</a>
</div>

<div class="card card-av">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr><th>Nombre</th><th>Código</th><th>Inicio</th><th>Fin</th><th>Estado</th><th class="text-end">Acciones</th></tr>
            </thead>
            <tbody>
            <?php foreach ($periods as $p): ?>
                <tr>
                    <td><?= e($p->name) ?></td>
                    <td><?= e($p->code) ?></td>
                    <td><?= e($p->start_date) ?></td>
                    <td><?= e($p->end_date) ?></td>
                    <td><span class="badge bg-secondary"><?= e($p->status) ?></span></td>
                    <td class="text-end">
                        <a href="<?= url('/academic-periods/' . $p->id . '/edit') ?>" class="btn btn-sm btn-outline-secondary">Editar</a>
                        <?php
                        $order = ['DRAFT' => 0, 'ACTIVE' => 1, 'CLOSED' => 2, 'ARCHIVED' => 3];
                        $next = ['DRAFT' => 'ACTIVE', 'ACTIVE' => 'CLOSED', 'CLOSED' => 'ARCHIVED'];
                        if (isset($next[$p->status])):
                        ?>
                        <form method="post" action="<?= url('/academic-periods/' . $p->id . '/transition') ?>" class="d-inline">
                            <?= csrf_field() ?>
                            <input type="hidden" name="status" value="<?= e($next[$p->status]) ?>">
                            <button class="btn btn-sm btn-outline-primary"><?= e(ucfirst(strtolower($next[$p->status]))) ?></button>
                        </form>
                        <?php endif; ?>
                        <form method="post" action="<?= url('/academic-periods/' . $p->id . '/delete') ?>" class="d-inline"
                              onsubmit="return confirm('¿Eliminar este período?');">
                            <?= csrf_field() ?>
                            <button class="btn btn-sm btn-outline-danger">Eliminar</button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
            <?php if (! $periods): ?><tr><td colspan="6" class="text-center text-muted py-4">No hay períodos registrados.</td></tr><?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
