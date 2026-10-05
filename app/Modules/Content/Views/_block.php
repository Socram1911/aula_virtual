<?php
/** @var \App\Modules\Content\Models\ContentBlock $block  Renderizado de lectura de un bloque. */
use App\Core\Auth\Auth;
use App\Core\Csrf\Csrf;

$isStudent = Auth::check() && Auth::hasRole('STUDENT');

switch ($block->type) {
    case 'TEXT':
        echo '<div class="block-text">' . nl2br(e($block->content)) . '</div>';
        break;
    case 'IMAGE':
        echo '<img src="' . url('/files/' . (int) $block->content) . '" alt="' . e($block->title) . '" class="img-fluid rounded">';
        break;
    case 'VIDEO':
        echo '<div class="ratio ratio-16x9"><iframe src="https://www.youtube.com/embed/' . e($block->content) . '" title="' . e($block->title) . '" allowfullscreen></iframe></div>';
        break;
    case 'PDF':
        echo '<a href="' . url('/files/' . (int) $block->content) . '" target="_blank" class="btn btn-outline-danger">' . e($block->title ?: 'Abrir PDF') . '</a>';
        break;
    case 'LINK':
        echo '<a href="' . e($block->content) . '" target="_blank" rel="noopener">' . e($block->title ?: $block->content) . '</a>';
        break;
    case 'EMBED':
        echo $block->content; // HTML de inserción confiable (solo docentes).
        break;
    case 'H5P':
        echo '<div class="h5p-container w-100"'
            . ' data-h5p-path="' . e(url('/h5p/' . (int) $block->id)) . '"'
            . ' data-frame-js="' . e(url('/assets/h5p-standalone/frame.bundle.js')) . '"'
            . ' data-frame-css="' . e(url('/assets/h5p-standalone/styles/h5p.css')) . '">'
            . '<div class="text-muted small">Cargando contenido interactivo…</div></div>';
        break;
    case 'ACTIVITY':
        $s = $block->settingsArray();
        $type = (string) ($s['type'] ?? 'SINGLE_ANSWER');
        $multiple = $type === 'MULTIPLE_CHOICE';
        echo '<div class="activity" data-activity-id="' . (int) $block->id . '">';
        echo '<p class="fw-semibold mb-2">' . e($s['question'] ?? '') . '</p>';
        foreach (($s['options'] ?? []) as $i => $opt) {
            echo '<div class="form-check">';
            echo '<input class="form-check-input" type="' . ($multiple ? 'checkbox' : 'radio') . '" name="answer" value="' . $i . '" id="act' . (int) $block->id . '_' . $i . '" ' . ($isStudent ? '' : 'disabled') . '>';
            echo '<label class="form-check-label" for="act' . (int) $block->id . '_' . $i . '">' . e($opt['text']) . '</label>';
            echo '</div>';
        }
        if ($isStudent) {
            echo '<button type="button" class="btn btn-sm btn-primary mt-2 activity-submit">Enviar respuesta</button>';
        }
        echo '<div class="activity-feedback mt-2 small fw-semibold"></div>';
        echo '</div>';
        break;
    default:
        echo '<span class="text-muted">' . e($block->type) . '</span>';
}
