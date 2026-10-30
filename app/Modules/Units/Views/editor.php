<?php
/** @var \App\Modules\Sabers\Models\Unit $unit @var array $blocks @var array $types */
$textBlocks = [];
foreach ($blocks as $b) {
    if ($b->type === 'TEXT') {
        $textBlocks[$b->id] = ['title' => $b->title, 'content' => $b->content];
    }
}
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

                        <div class="mt-2">
                            <?php
                            $view = 'app/Modules/Content/Views/_block.php';
                            if (is_file(dirname(__DIR__, 4) . '/' . $view)) {
                                $block = $b;
                                include dirname(__DIR__, 4) . '/' . $view;
                            }
                            ?>
                        </div>

                        <?php if ($b->type === 'TEXT'): ?>
                            <button type="button" class="btn btn-sm btn-outline-primary mt-2 edit-text-btn"
                                    data-id="<?= (int) $b->id ?>">✎ Editar texto</button>
                        <?php else: ?>
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
                        <?php endif; ?>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <div class="col-lg-4">
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
                    <div class="form-check mb-2">
                        <input class="form-check-input" type="checkbox" name="evaluable" value="1" id="h5p-evaluable">
                        <label class="form-check-label" for="h5p-evaluable">Evaluable (cuenta para la nota del Saber, un solo intento)</label>
                    </div>
                    <button class="btn btn-info w-100 text-white">Subir H5P</button>
                </form>
            </div>
        </div>

        <div class="card card-av">
            <div class="card-body">
                <h2 class="h6">Agregar bloque</h2>
                <form method="post" action="<?= url('/units/' . $unit->id . '/blocks') ?>" enctype="multipart/form-data" id="add-block-form">
                    <?= csrf_field() ?>
                    <div class="mb-2">
                        <label class="form-label">Tipo</label>
                        <select name="type" class="form-select" id="block-type">
                            <option value="TEXT" selected>Texto enriquecido</option>
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
                    <input type="hidden" name="content" id="block-content">
                    <div class="mb-2" id="rich-editor-field">
                        <label class="form-label">Contenido</label>
                        <div id="add-block-editor" style="min-height:160px"></div>
                    </div>
                    <div class="mb-2 d-none" id="content-field">
                        <label class="form-label">Contenido</label>
                        <input type="text" id="block-content-plain" class="form-control" placeholder="Texto, URL o id de YouTube">
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

<!-- Modal de edición de texto enriquecido -->
<div class="modal fade" id="edit-text-modal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <form method="post" action="" id="edit-text-form"
                  data-action-template="<?= e(url('/blocks/__ID__')) ?>">
                <?= csrf_field() ?>
                <div class="modal-header">
                    <h5 class="modal-title">Editar texto</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="content" id="edit-text-content">
                    <div class="mb-2">
                        <label class="form-label">Título</label>
                        <input type="text" name="title" class="form-control" id="edit-text-title">
                    </div>
                    <label class="form-label">Contenido</label>
                    <div id="edit-text-editor" style="min-height:220px"></div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary">Guardar cambios</button>
                </div>
            </form>
        </div>
    </div>
</div>

<link href="https://cdn.jsdelivr.net/npm/quill@2.0.3/dist/quill.snow.css" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/quill@2.0.3/dist/quill.js"></script>
<script>
(function () {
    'use strict';

    var TEXT_BLOCKS = <?= json_encode($textBlocks, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;

    var addQuill = new Quill('#add-block-editor', {
        theme: 'snow',
        placeholder: 'Escribe el contenido enriquecido…',
        modules: {
            toolbar: [
                [{ 'header': [2, 3, 4, false] }],
                ['bold', 'italic', 'underline', 'strike'],
                [{ 'list': 'ordered' }, { 'list': 'bullet' }],
                ['blockquote', 'code-block', 'link'],
                ['clean']
            ]
        }
    });

    var editQuill = new Quill('#edit-text-editor', {
        theme: 'snow',
        placeholder: 'Escribe el contenido enriquecido…',
        modules: {
            toolbar: [
                [{ 'header': [2, 3, 4, false] }],
                ['bold', 'italic', 'underline', 'strike'],
                [{ 'list': 'ordered' }, { 'list': 'bullet' }],
                ['blockquote', 'code-block', 'link'],
                ['clean']
            ]
        }
    });

    function toggleFields(type) {
        var isFile = type === 'IMAGE' || type === 'PDF';
        var isText = type === 'TEXT';
        document.getElementById('rich-editor-field').classList.toggle('d-none', !isText);
        document.getElementById('content-field').classList.toggle('d-none', isText || isFile);
        document.getElementById('file-field').classList.toggle('d-none', !isFile);
    }

    var typeSelect = document.getElementById('block-type');
    typeSelect.addEventListener('change', function () {
        toggleFields(this.value);
    });
    toggleFields(typeSelect.value);

    document.getElementById('add-block-form').addEventListener('submit', function () {
        if (typeSelect.value === 'TEXT') {
            document.getElementById('block-content').value = addQuill.getSemanticHTML();
        } else {
            document.getElementById('block-content').value = document.getElementById('block-content-plain').value;
        }
    });

    // Edición de bloques de texto existentes.
    var modalEl = document.getElementById('edit-text-modal');
    var modal = null; // Bootstrap se carga al final del layout; se inicializa de forma perezosa.
    var editForm = document.getElementById('edit-text-form');
    var editTitle = document.getElementById('edit-text-title');
    var editContent = document.getElementById('edit-text-content');

    document.querySelectorAll('.edit-text-btn').forEach(function (btn) {
        btn.addEventListener('click', function () {
            var data = TEXT_BLOCKS[btn.dataset.id] || { title: '', content: '' };
            editForm.action = editForm.dataset.actionTemplate.replace('__ID__', btn.dataset.id);
            editTitle.value = data.title;
            editQuill.root.innerHTML = data.content;
            editContent.value = data.content;
            if (!modal) {
                modal = new bootstrap.Modal(modalEl);
            }
            modal.show();
        });
    });

    editForm.addEventListener('submit', function () {
        editContent.value = editQuill.getSemanticHTML();
    });
})();
</script>
