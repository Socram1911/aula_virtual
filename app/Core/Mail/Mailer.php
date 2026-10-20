<?php

declare(strict_types=1);

namespace App\Core\Mail;

/**
 * Envío de correos transaccionales mediante la API de Resend (resend.com).
 *
 * Configuración en .env:
 *   RESEND_API_KEY=re_xxxx
 *   RESEND_FROM="Aula Virtual Académica <onboarding@resend.dev>"
 *
 * Devuelve siempre un arreglo [ok, message, id] para que el llamador pueda
 * informar al usuario qué ocurrió (nunca falla en silencio).
 */
final class Mailer
{
    private const ENDPOINT = 'https://api.resend.com/emails';

    /**
     * Envía un correo.
     *
     * @return array{ok: bool, message: string, id: ?string}
     */
    public static function send(string $to, string $subject, string $html, string $text = ''): array
    {
        $apiKey = (string) config('RESEND_API_KEY', '');
        if ($apiKey === '') {
            return ['ok' => false, 'message' => 'RESEND_API_KEY no está configurada en .env', 'id' => null];
        }

        $from = (string) config('RESEND_FROM', 'Aula Virtual Académica <onboarding@resend.dev>');
        $payload = [
            'from' => $from,
            'to' => [$to],
            'subject' => $subject,
            'html' => $html,
            'text' => $text !== '' ? $text : strip_tags($html),
        ];

        $result = self::post($apiKey, $payload);

        if ($result['status'] >= 200 && $result['status'] < 300) {
            $id = self::extractId($result['body']);
            return ['ok' => true, 'message' => 'Correo enviado.', 'id' => $id];
        }

        return [
            'ok' => false,
            'message' => self::extractErrorMessage($result['body'], $result['status']),
            'id' => null,
        ];
    }

    /** @return array{status: int, body: string} */
    private static function post(string $apiKey, array $payload): array
    {
        $json = json_encode($payload, JSON_UNESCAPED_UNICODE);

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
            if ($response === false) {
                $error = curl_error($ch);
                curl_close($ch);
                return ['status' => 0, 'body' => 'Error de conexión: ' . $error];
            }
            $status = (int) curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
            curl_close($ch);
            return ['status' => $status, 'body' => (string) $response];
        }

        $context = stream_context_create([
            'http' => [
                'method' => 'POST',
                'header' => "Authorization: Bearer {$apiKey}\r\nContent-Type: application/json\r\n",
                'content' => $json,
                'timeout' => 15,
                'ignore_errors' => true,
            ],
        ]);
        $response = @file_get_contents(self::ENDPOINT, false, $context);
        if ($response === false) {
            return ['status' => 0, 'body' => 'Error de conexión con la API de Resend.'];
        }
        $status = 0;
        foreach ($http_response_header ?? [] as $header) {
            if (preg_match('#^HTTP/\S+\s+(\d{3})#', $header, $m)) {
                $status = (int) $m[1];
            }
        }
        return ['status' => $status, 'body' => (string) $response];
    }

    private static function extractId(string $body): ?string
    {
        $data = json_decode($body, true);
        return is_array($data) && isset($data['id']) ? (string) $data['id'] : null;
    }

    private static function extractErrorMessage(string $body, int $status): string
    {
        $data = json_decode($body, true);
        if (is_array($data)) {
            $msg = (string) ($data['message'] ?? '');
            if ($msg !== '') {
                return "Resend ({$status}): {$msg}";
            }
        }
        return "Resend devolvió un error ({$status}).";
    }
}
