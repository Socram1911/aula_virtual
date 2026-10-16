<?php

declare(strict_types=1);

namespace App\Core\Mail;

/**
 * Envío de correos transaccionales mediante la API de Resend (resend.com).
 *
 * Configuración requerida en .env:
 *   RESEND_API_KEY=re_xxxx
 *   RESEND_FROM="Aula Virtual Académica <no-reply@tu-dominio.com>"
 *
 * Si RESEND_API_KEY no está configurada, send() devuelve false (sin lanzar excepción)
 * para que los flujos sigan funcionando en desarrollo sin un proveedor de correo.
 */
final class Mailer
{
    private const ENDPOINT = 'https://api.resend.com/emails';

    /** Envía un correo. Devuelve true si fue aceptado por Resend, false en caso contrario. */
    public static function send(string $to, string $subject, string $html, string $text = ''): bool
    {
        $apiKey = (string) config('RESEND_API_KEY', '');
        if ($apiKey === '') {
            return false;
        }

        $from = (string) config('RESEND_FROM', 'Aula Virtual Académica <no-reply@resend.dev>');
        $payload = [
            'from' => $from,
            'to' => [$to],
            'subject' => $subject,
            'html' => $html,
            'text' => $text !== '' ? $text : strip_tags($html),
        ];

        return self::post($apiKey, $payload);
    }

    private static function post(string $apiKey, array $payload): bool
    {
        $json = json_encode($payload, JSON_UNESCAPED_UNICODE);

        // Preferencia: ext-curl.
        if (function_exists('curl_init')) {
            $ch = curl_init(self::ENDPOINT);
            curl_setopt_array($ch, [
                CURLOPT_POST => true,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_HTTPHEADER => [
                    'Authorization: Bearer ' . $apiKey,
                    'Content-Type: application/json',
                ],
                CURLOPT_POSTFIELDS => $json,
                CURLOPT_TIMEOUT => 15,
            ]);
            $response = curl_exec($ch);
            $status = (int) curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
            curl_close($ch);

            return $status >= 200 && $status < 300 && $response !== false;
        }

        // Respaldo: streams (require allow_url_fopen).
        $context = stream_context_create([
            'http' => [
                'method' => 'POST',
                'header' => "Authorization: Bearer {$apiKey}\r\n"
                    . "Content-Type: application/json\r\n",
                'content' => $json,
                'timeout' => 15,
                'ignore_errors' => true,
            ],
        ]);
        $response = @file_get_contents(self::ENDPOINT, false, $context);
        if ($response === false) {
            return false;
        }
        $status = 0;
        foreach ($http_response_header ?? [] as $header) {
            if (preg_match('#^HTTP/\S+\s+(\d{3})#', $header, $m)) {
                $status = (int) $m[1];
            }
        }
        return $status >= 200 && $status < 300;
    }
}
