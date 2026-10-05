<?php /** @var array $users @var string $title */ ?>
<div class="d-flex justify-content-between align-items-center mb-3">
    <h1 class="h4 mb-0"><?= e($title) ?></h1>
    <a href="<?= url('/users/create') ?>" class="btn btn-primary btn-sm">+ Nuevo usuario</a>
</div>
<div class="card card-av">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr><th>Nombre</th><th>Correo</th><th>Roles</th><th>Estado</th><th class="text-end">Acciones</th></tr>
            </thead>
            <tbody>
            <?php foreach ($users as $u): ?>
                <tr>
                    <td><?= e($u->last_name . ', ' . $u->first_name) ?></td>
                    <td><?= e($u->email) ?></td>
                    <td>
                        <?php foreach ($u->roles as $r): ?>
                            <span class="badge bg-info text-dark"><?= e($r) ?></span>
                        <?php endforeach; ?>
                    </td>
                    <td><span class="badge <?= $u->status === 'ACTIVE' ? 'bg-success' : 'bg-secondary' ?>"><?= e($u->status) ?></span></td>
                    <td class="text-end">
                        <a href="<?= url('/users/' . $u->id . '/edit') ?>" class="btn btn-sm btn-outline-secondary">Editar</a>
                        <form method="post" action="<?= url('/users/' . $u->id . '/delete') ?>" class="d-inline" onsubmit="return confirm('¿Eliminar este usuario?');">
                            <?= csrf_field() ?>
                            <button class="btn btn-sm btn-outline-danger">Eliminar</button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
            <?php if (! $users): ?><tr><td colspan="5" class="text-center text-muted py-4">No hay usuarios.</td></tr><?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
