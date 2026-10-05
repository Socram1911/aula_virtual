<?php /** @var array $sabers */ ?>
<div class="d-flex justify-content-between align-items-center mb-3">
    <h1 class="h4 mb-0">Saberes</h1>
    <a href="<?= url('/sabers/create') ?>" class="btn btn-primary btn-sm">+ Nuevo saber</a>
</div>
<div class="card card-av">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr><th>Código</th><th>Nombre</th><th>Trayecto</th><th>Desbloqueo</th><th>Estado</th><th class="text-end">Acciones</th></tr>
            </thead>
            <tbody>
            <?php foreach ($sabers as $s): ?>
                <tr>
                    <td><?= e($s->code) ?></td>
                    <td><?= e($s->name) ?></td>
                    <td><?= e($s->track_name) ?></td>
                    <td><?= $s->unlock_mode === 'SEQUENTIAL' ? 'Secuencial' : 'Libre' ?></td>
                    <td><span class="badge <?= $s->status === 'ACTIVE' ? 'bg-success' : 'bg-secondary' ?>"><?= e($s->status) ?></span></td>
                    <td class="text-end">
                        <a href="<?= url('/sabers/' . $s->id . '/units') ?>" class="btn btn-sm btn-outline-primary">Unidades</a>
                        <a href="<?= url('/sabers/' . $s->id . '/edit') ?>" class="btn btn-sm btn-outline-secondary">Editar</a>
                        <form method="post" action="<?= url('/sabers/' . $s->id . '/delete') ?>" class="d-inline" onsubmit="return confirm('¿Eliminar este saber?');">
                            <?= csrf_field() ?>
                            <button class="btn btn-sm btn-outline-danger">Eliminar</button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
            <?php if (! $sabers): ?><tr><td colspan="6" class="text-center text-muted py-4">No hay saberes registrados.</td></tr><?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
