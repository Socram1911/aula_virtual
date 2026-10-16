<?php

declare(strict_types=1);

namespace App\Core\Html;

/**
 * Sanitizador de HTML para contenido de bloques de texto enriquecido.
 *
 * Usa una lista de permitidos (allowlist): conserva solo etiquetas seguras,
 * elimina todos los atributos (incluidos eventos y estilos) y solo permite
 * enlaces con protocolo seguro (http/https/mailto/anclas).
 */
final class HtmlSanitizer
{
    private const ALLOWED_TAGS = '<p><br><strong><b><em><i><u><s><h2><h3><h4><h5><ul><ol><li><a><blockquote><code><pre><span><hr><sub><sup>';

    public static function clean(?string $html): string
    {
        if ($html === null || trim($html) === '') {
            return '';
        }

        // Elimina por completo bloques peligrosos (script/style) y su contenido.
        $clean = preg_replace('#<(script|style|iframe|object|embed|form)\b[^>]*>.*?</\1>#is', '', $html) ?? $html;
        $clean = strip_tags($clean, self::ALLOWED_TAGS);

        // 1) <a>: se procesa aparte para conservar únicamente un href válido.
        //    Se sustituye temporalmente por marcadores para que el paso 2 no lo altere.
        $anchors = [];
        $clean = preg_replace_callback('/<a\b[^>]*>/i', function (array $m) use (&$anchors): string {
            $key = "\x01" . count($anchors) . "\x02";
            $anchors[$key] = self::cleanAnchor($m);
            return $key;
        }, $clean) ?? $clean;

        // 2) Resto de etiquetas permitidas: eliminar todos los atributos restantes.
        $clean = preg_replace('/<(\/?)([a-zA-Z][a-zA-Z0-9]*)\s[^>]*>/', '<$1$2>', $clean) ?? $clean;

        // 3) Restaurar los enlaces sanitizados.
        $clean = strtr($clean, $anchors);

        return trim($clean);
    }

    private static function cleanAnchor(array $matches): string
    {
        $tag = $matches[0];
        if (preg_match('/href\s*=\s*("([^"]*)"|\'([^\']*)\'|([^\s>]+))/i', $tag, $m)) {
            $url = html_entity_decode((string) ($m[2] !== '' ? $m[2] : ($m[3] !== '' ? $m[3] : ($m[4] ?? ''))), ENT_QUOTES, 'UTF-8');
            $url = trim($url);
            if (preg_match('~^(https?://|mailto:|#|/)~i', $url)) {
                return '<a href="' . htmlspecialchars($url, ENT_QUOTES, 'UTF-8') . '">';
            }
        }
        return '<a>';
    }
}
