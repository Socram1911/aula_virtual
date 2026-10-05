<?php
/** @var \App\Modules\Sabers\Models\Unit $unit @var array $blocks @var array $types */
?>
<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h1 class="h4 mb-1"><?= e($unit->title) ?></h1>
        <span class="text-muted">Editor de contenido · Unidad #<?= (int) $unit->id ?></span>
    </div>
    <a href="<?= url('/sabers/' . $unit->saber_id . '/units') ?>" class="btn btn-outline-secondary btn-sm">Volver</a>
</div>

<div class="row g-3">
    <div class="col-lg-8">
        <div class="card card-av">
            <div class="card-body">
                <h2 class="h6">Bloques de contenido</h2>
                <?php if (! $blocks): ?>
                    <p class="text-muted">Esta unidad no tiene bloques. Agregue el primero con el formulario de la derecha.</p>
                <?php endif; ?>
                <?php foreach ($blocks as $b): ?>
                <div class="card mb-2 <?= $b->status === 'INACTIVE' ? 'block-inactive' : 'block-sortable' ?>">
                    <div class="card-body py-2">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <span class="badge bg-primary"><?= e($b->type) ?></span>
                                <strong class="ms-2"><?= e($b->title ?: '(sin título)') ?></strong>
                                <span class="text-muted small ms-2">#<?= (int) $b->position ?></span>
                                <?php if ($b->status === 'INACTIVE'): ?><span class="badge bg-secondary ms-1">Inactivo</span><?php endif; ?>
                            </div>
                            <div class="btn-group btn-group-sm">
                                <form method="post" action="<?= url('/blocks/' . $b->id . '/move') ?>" class="d-inline">
                                    <?= csrf_field() ?><input type="hidden" name="direction" value="up">
                                    <button class="btn btn-outline-secondary">↑</button>
                                </form>
                                <form method="post" action="<?= url('/blocks/' . $b->id . '/move') ?>" class="d-inline">
                                    <?= csrf_field() ?><input type="hidden" name="direction" value="down">
                                    <button class="btn btn-outline-secondary">↓</button>
                                </form>
                                <form method="post" action="<?= url('/blocks/' . $b->id . '/toggle') ?>" class="d-inline">
                                    <?= csrf_field() ?>
                                    <button class="btn btn-outline-warning"><?= $b->status === 'ACTIVE' ? 'Desactivar' : 'Activar' ?></button>
                                </form>
                                <form method="post" action="<?= url('/blocks/' . $b->id . '/duplicate') ?>" class="d-inline">
                                    <?= csrf_field() ?>
                                    <button class="btn btn-outline-info">Duplicar</button>
                                </form>
                                <form method="post" action="<?= url('/blocks/' . $b->id . '/delete') ?>" class="d-inline" onsubmit="return confirm('¿Eliminar este bloque?');">
                                    <?= csrf_field() ?>
                                    <button class="btn btn-outline-danger">Eliminar</button>
                                </form>
                            </div>
                        </div>

                        <div class="mt-2 small">
                            <?php
                            $view = 'app/Modules/Content/Views/_block.php';
                            if (is_file(dirname(__DIR__, 4) . '/' . $view)) {
                                $block = $b;
                                include dirname(__DIR__, 4) . '/' . $view;
                            }
                            ?>
                        </div>

                        <details class="mt-2">
                            <summary class="text-muted small">Editar</summary>
                            <form method="post" action="<?= url('/blocks/' . $b->id) ?>" class="mt-2">
                                <?= csrf_field() ?>
                                <div class="row g-2">
                                    <div class="col-md-4">
                                        <input type="text" name="title" class="form-control form-control-sm" value="<?= e($b->title) ?>" placeholder="Título">
                                    </div>
                                    <div class="col-md-8">
                                        <input type="text" name="content" class="form-control form-control-sm" value="<?= e($b->content) ?>" placeholder="Contenido (texto, URL o id de video)">
                                    </div>
                                    <div class="col-12">
                                        <button class="btn btn-sm btn-primary">Guardar</button>
                                    </div>
                                </div>
                            </form>
                        </details>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <div class="col-lg-4">
        <div class="card card-av mb-3">
            <div class="card-body">
                <h2 class="h6">Agregar actividad</h2>
                <form method="post" action="<?= url('/units/' . $unit->id . '/activities') ?>">
                    <?= csrf_field() ?>
                    <div class="mb-2">
                        <label class="form-label">Tipo</label>
                        <select name="activity_type" class="form-select">
                            <option value="SINGLE_ANSWER">Respuesta única</option>
                            <option value="MULTIPLE_CHOICE">Selección múltiple</option>
                            <option value="TRUE_FALSE">Verdadero / Falso</option>
                        </select>
                    </div>
                    <div class="mb-2">
                        <label class="form-label">Pregunta</label>
                        <input type="text" name="question" class="form-control" required>
                    </div>
                    <div class="mb-2">
                        <label class="form-label">Clasificación</label>
                        <select name="classification" class="form-select">
                            <option value="FORMATIVE">Formativa (práctica)</option>
                            <option value="GRADED">Calificable</option>
                        </select>
                    </div>
                    <div class="mb-2">
                        <label class="form-label">Opciones (marca la correcta)</label>
                        <?php for ($i = 0; $i < 4; $i++): ?>
                            <div class="input-group input-group-sm mb-1">
                                <span class="input-group-text">
                                    <input class="form-check-input mt-0" type="radio" name="options[<?= $i ?>][correct]" value="1" <?= $i === 0 ? 'checked' : '' ?> title="Correcta">
                                </span>
                                <input type="text" name="options[<?= $i ?>][text]" class="form-control" placeholder="Opción <?= $i + 1 ?>">
                            </div>
                        <?php endfor; ?>
                    </div>
                    <button class="btn btn-success w-100">Agregar actividad</button>
                </form>
            </div>
        </div>

        <div class="card card-av mb-3">
            <div class="card-body">
                <h2 class="h6">Agregar contenido H5P</h2>
                <p class="small text-muted">Suba un paquete <code>.h5p</code> (creado con Lumi o descargado de H5P.org).</p>
                <form method="post" action="<?= url('/units/' . $unit->id . '/h5p') ?>" enctype="multipart/form-data">
                    <?= csrf_field() ?>
                    <div class="mb-2">
                        <label class="form-label">Título</label>
                        <input type="text" name="title" class="form-control" placeholder="Título opcional">
                    </div>
                    <div class="mb-2">
                        <input type="file" name="file" class="form-control" accept=".h5p" required>
                    </div>
                    <button class="btn btn-info w-100 text-white">Subir H5P</button>
                </form>
            </div>
        </div>

        <div class="card card-av">
            <div class="card-body">
                <h2 class="h6">Agregar bloque</h2>
                <form method="post" action="<?= url('/units/' . $unit->id . '/blocks') ?>" enctype="multipart/form-data">
                    <?= csrf_field() ?>
                    <div class="mb-2">
                        <label class="form-label">Tipo</label>
                        <select name="type" class="form-select" id="block-type">
                            <option value="TEXT">Texto</option>
                            <option value="IMAGE">Imagen</option>
                            <option value="VIDEO">Video (YouTube)</option>
                            <option value="PDF">PDF</option>
                            <option value="LINK">Enlace</option>
                            <option value="EMBED">Insertar (HTML)</option>
                        </select>
                    </div>
                    <div class="mb-2">
                        <label class="form-label">Título</label>
                        <input type="text" name="title" class="form-control" placeholder="Título opcional">
                    </div>
                    <div class="mb-2" id="content-field">
                        <label class="form-label">Contenido</label>
                        <input type="text" name="content" class="form-control" placeholder="Texto, URL o id de YouTube">
                    </div>
                    <div class="mb-2 d-none" id="file-field">
                        <label class="form-label">Archivo</label>
                        <input type="file" name="file" class="form-control">
                    </div>
                    <button class="btn btn-primary w-100">Agregar bloque</button>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
document.getElementById('block-type').addEventListener('change', function () {
    var isFile = this.value === 'IMAGE' || this.value === 'PDF';
    document.getElementById('content-field').classList.toggle('d-none', isFile);
    document.getElementById('file-field').classList.toggle('d-none', !isFile);
});
</script>
