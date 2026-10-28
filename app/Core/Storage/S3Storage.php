<?php

declare(strict_types=1);

namespace App\Core\Storage;

use Aws\S3\Exception\S3Exception;
use Aws\S3\S3Client;
use Psr\Http\Message\StreamInterface;

/**
 * Cliente de almacenamiento S3-compatible (Cloudflare R2).
 *
 * Los archivos subidos por usuarios y los paquetes H5P se guardan aquí como
 * objetos, en lugar del disco local (que es efímero en Render/Cloud Run).
 */
final class S3Storage
{
    private ?S3Client $client = null;

    private function client(): S3Client
    {
        if ($this->client === null) {
            $this->client = new S3Client([
                'version' => 'latest',
                'region' => (string) config('R2_REGION', 'auto'),
                'endpoint' => (string) config('R2_ENDPOINT', ''),
                'credentials' => [
                    'key' => (string) config('R2_ACCESS_KEY', ''),
                    'secret' => (string) config('R2_SECRET_KEY', ''),
                ],
                'use_path_style_endpoint' => true,
            ]);
        }

        return $this->client;
    }

    private function bucket(): string
    {
        $bucket = (string) config('R2_BUCKET', '');
        if ($bucket === '') {
            throw new \RuntimeException('R2_BUCKET no está configurada en .env');
        }

        return $bucket;
    }

    /** Sube un archivo local a R2. */
    public function put(string $key, string $sourceFile, string $contentType): void
    {
        $this->client()->putObject([
            'Bucket' => $this->bucket(),
            'Key' => $key,
            'SourceFile' => $sourceFile,
            'ContentType' => $contentType,
        ]);
    }

    /** Elimina un objeto. */
    public function delete(string $key): void
    {
        $this->client()->deleteObject([
            'Bucket' => $this->bucket(),
            'Key' => $key,
        ]);
    }

    /** Elimina todos los objetos bajo un prefijo (limpieza de bloques H5P). */
    public function deletePrefix(string $prefix): void
    {
        $client = $this->client();
        $bucket = $this->bucket();

        do {
            $args = ['Bucket' => $bucket, 'Prefix' => $prefix];
            if (isset($token)) {
                $args['ContinuationToken'] = $token;
            }
            $objects = $client->listObjectsV2($args);
            $keys = [];
            foreach (($objects['Contents'] ?? []) as $object) {
                $keys[] = ['Key' => $object['Key']];
            }
            if ($keys !== []) {
                $client->deleteObjects([
                    'Bucket' => $bucket,
                    'Delete' => ['Objects' => $keys],
                ]);
            }
            $token = $objects['NextContinuationToken'] ?? null;
        } while ($token !== null);
    }

    /**
     * Transmite un objeto a la salida HTTP (con soporte de Range) y termina el
     * script. Usado para servir archivos y contenido H5P con control de acceso.
     */
    public function stream(string $key, ?string $fallbackMime = null, ?string $filename = null, bool $inline = true): void
    {
        $args = [
            'Bucket' => $this->bucket(),
            'Key' => $key,
        ];

        $range = $_SERVER['HTTP_RANGE'] ?? null;
        if (is_string($range) && $range !== '') {
            $args['Range'] = $range;
        }

        try {
            $result = $this->client()->getObject($args);
        } catch (S3Exception $e) {
            $status = $e->getStatusCode() ?: 0;
            http_response_code(in_array($status, [404, 416], true) ? $status : 502);
            exit;
        }

        $contentType = (string) ($result['ContentType'] ?? $fallbackMime ?? 'application/octet-stream');
        if ($contentType === '') {
            $contentType = $fallbackMime ?? 'application/octet-stream';
        }
        header('Content-Type: ' . $contentType);
        header('Accept-Ranges: bytes');

        if (! empty($result['ContentRange'])) {
            http_response_code(206);
            header('Content-Range: ' . $result['ContentRange']);
            if (isset($result['ContentLength'])) {
                header('Content-Length: ' . (string) $result['ContentLength']);
            }
        } elseif (isset($result['ContentLength'])) {
            header('Content-Length: ' . (string) $result['ContentLength']);
        }

        if ($filename !== null && $filename !== '') {
            $safe = str_replace(["\r", "\n", '"'], '', $filename);
            header('Content-Disposition: ' . ($inline ? 'inline' : 'attachment') . '; filename="' . $safe . '"');
        }

        $body = $result['Body'] ?? null;
        if ($body instanceof StreamInterface) {
            // Importante: rewind antes de leer; el cuerpo llega con la posición
            // al final y, sin rebobinar, se copiarían 0 bytes.
            $body->rewind();
            while (! $body->eof()) {
                echo $body->read(65536);
                if (ob_get_level() > 0) {
                    ob_flush();
                }
                flush();
            }
        } elseif (is_resource($body)) {
            $meta = stream_get_meta_data($body);
            if (($meta['seekable'] ?? false) === true) {
                rewind($body);
            }
            $out = fopen('php://output', 'wb');
            if ($out !== false) {
                stream_copy_to_stream($body, $out);
                fclose($out);
            }
        } elseif ($body !== null) {
            echo (string) $body;
        }

        exit;
    }
}
