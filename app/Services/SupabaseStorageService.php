<?php

/**
 * @descripcion  Servicio de dominio SupabaseStorage: encapsula reglas de negocio reutilizables.
 *
 * @autor          Rubén Alejandro Nolasco Ruiz
 * @autorizador    Rubén Alejandro Nolasco Ruiz
 * @prueba         Diego Miguel Hernandez Fabela
 * @mantenimiento  Ghael Garcia Manjarrez
 *
 * @version      1.0.0
 * @creado       2026-06-02
 * @modificado   2026-06-02
 *
 * @cambios       *               2026-06-02 - Incorporación de cabecera de prólogo conforme estándar
 */


declare(strict_types=1);

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class SupabaseStorageService
{
    /** MIME types permitidos por bucket (incluye alias de nombres legacy). */
    public const ALLOWED_MIMES = [
        'institution-logos'     => ['image/jpeg', 'image/png', 'image/jpg'],
        'justification-files'   => ['application/pdf', 'image/jpeg', 'image/png', 'image/jpg'],
        'justificantes-adjuntos' => ['application/pdf', 'image/jpeg', 'image/png', 'image/jpg'],
    ];

    private string $url;

    private string $key;

    private ?string $lastError = null;

    /**
     * Carga URL y service key de Supabase desde la configuración de la aplicación.
     *
     * @return void
     */
    public function __construct()
    {
        $this->url = rtrim((string) config('services.supabase.url'), '/');
        $this->key = (string) config('services.supabase.service_key');
    }

    /**
     * Devuelve el último mensaje de error registrado tras upload o validación.
     *
     * @return string|null Mensaje en español o null si no hubo error reciente
     */
    public function getLastError(): ?string
    {
        return $this->lastError;
    }

    /**
     * Indica si SUPABASE_URL y SUPABASE_SERVICE_KEY están definidos.
     *
     * @return bool true si ambos valores de configuración son no vacíos
     */
    public function isConfigured(): bool
    {
        return $this->url !== '' && $this->key !== '';
    }

    /**
     * Nombre del bucket de justificantes según config o valor por defecto legacy.
     *
     * @return string Nombre del bucket en Supabase Storage
     */
    public function justificationsBucket(): string
    {
        $bucket = config('services.supabase.buckets.justifications');

        return ($bucket !== null && $bucket !== '') ? (string) $bucket : 'justificantes-adjuntos';
    }

    /**
     * Nombre del bucket de logos institucionales según config o valor por defecto.
     *
     * @return string Nombre del bucket en Supabase Storage
     */
    public function institutionLogosBucket(): string
    {
        $bucket = config('services.supabase.buckets.institution_logos');

        return ($bucket !== null && $bucket !== '') ? (string) $bucket : 'institution-logos';
    }

    /**
     * Valida que el MIME o extensión del archivo esté permitido para el bucket indicado.
     *
     * @param UploadedFile $file   Archivo subido por el usuario
     * @param string       $bucket Nombre del bucket (debe existir en ALLOWED_MIMES)
     * @return bool true si el tipo es aceptado para ese bucket
     */
    public function isAllowedMime(UploadedFile $file, string $bucket): bool
    {
        $allowed = self::ALLOWED_MIMES[$bucket] ?? [];

        if ($allowed === []) {
            return false;
        }

        $mime = strtolower((string) $file->getMimeType());

        if (in_array($mime, $allowed, true)) {
            return true;
        }

        // Windows / algunos navegadores envían application/octet-stream para PDF válidos.
        if ($mime === 'application/octet-stream') {
            return $this->extensionMatchesAllowed($file, $allowed);
        }

        return $this->extensionMatchesAllowed($file, $allowed);
    }

    /**
     * Sube un archivo al bucket de Supabase y devuelve la URL pública del objeto.
     *
     * @param UploadedFile $file   Archivo a subir
     * @param string       $bucket Nombre del bucket destino
     * @param string       $folder Prefijo de carpeta opcional dentro del bucket
     * @return string|null URL pública del objeto subido, o null si falla (ver getLastError)
     */
    public function upload(UploadedFile $file, string $bucket, string $folder = ''): ?string
    {
        $this->lastError = null;

        if (! $this->isConfigured()) {
            $this->lastError = 'Supabase no está configurado. Revise SUPABASE_URL y SUPABASE_SERVICE_KEY en .env.';

            return null;
        }

        if (! $this->isAllowedMime($file, $bucket)) {
            $this->lastError = 'Tipo de archivo no permitido para este bucket.';

            return null;
        }

        $extension = strtolower($file->getClientOriginalExtension() ?: 'bin');
        $filename  = $folder
            ? "{$folder}/" . Str::uuid() . ".{$extension}"
            : Str::uuid() . ".{$extension}";

        $path = rawurlencode($filename);
        // rawurlencode codifica / — preservar carpetas
        $path = str_replace('%2F', '/', $path);

        $contentType = $file->getMimeType() ?: 'application/octet-stream';
        if ($extension === 'pdf' && ! str_contains($contentType, 'pdf')) {
            $contentType = 'application/pdf';
        }

        $response = Http::withHeaders($this->authHeaders([
            'Content-Type' => $contentType,
            'x-upsert'     => 'true',
        ]))
            ->when(app()->environment('local'), fn ($http) => $http->withoutVerifying())
            ->withBody(
                file_get_contents($file->getRealPath()),
                $contentType
            )
            ->post("{$this->url}/storage/v1/object/{$bucket}/{$path}");

        if ($response->successful()) {
            return $this->publicObjectUrl($bucket, $filename);
        }

        $this->lastError = $this->parseErrorMessage($response->status(), $response->json(), $response->body());

        Log::warning('Supabase upload failed', [
            'bucket'   => $bucket,
            'path'     => $filename,
            'status'   => $response->status(),
            'message'  => $this->lastError,
        ]);

        return null;
    }

    /**
     * Elimina un objeto del bucket a partir de su URL pública.
     *
     * @param string $bucket Nombre del bucket
     * @param string $url    URL pública devuelta por upload o getPublicUrl
     * @return bool true si la API de Supabase respondió con éxito
     */
    public function delete(string $bucket, string $url): bool
    {
        if (! $this->isConfigured()) {
            return false;
        }

        $prefix = "{$this->url}/storage/v1/object/public/{$bucket}/";
        $path   = str_replace($prefix, '', $url);

        $response = Http::withHeaders($this->authHeaders())
            ->when(app()->environment('local'), fn ($http) => $http->withoutVerifying())
            ->delete("{$this->url}/storage/v1/object/{$bucket}", [
                'prefixes' => [$path],
            ]);

        return $response->successful();
    }

    /**
     * Construye la URL pública de un objeto sin subirlo (ruta relativa dentro del bucket).
     *
     * @param string $bucket Nombre del bucket
     * @param string $path   Ruta del objeto dentro del bucket
     * @return string URL pública codificada
     */
    public function getPublicUrl(string $bucket, string $path): string
    {
        return $this->publicObjectUrl($bucket, $path);
    }

    private function authHeaders(array $extra = []): array
    {
        return array_merge([
            'Authorization' => "Bearer {$this->key}",
            'apikey'          => $this->key,
        ], $extra);
    }

    private function publicObjectUrl(string $bucket, string $path): string
    {
        $encoded = implode('/', array_map('rawurlencode', explode('/', $path)));

        return "{$this->url}/storage/v1/object/public/{$bucket}/{$encoded}";
    }

    private function extensionMatchesAllowed(UploadedFile $file, array $allowed): bool
    {
        $ext = strtolower($file->getClientOriginalExtension());
        $map = [
            'pdf'  => 'application/pdf',
            'jpg'  => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'png'  => 'image/png',
        ];

        if (! isset($map[$ext])) {
            return false;
        }

        return in_array($map[$ext], $allowed, true);
    }

    private function parseErrorMessage(int $status, mixed $json, string $body): string
    {
        if (is_array($json)) {
            $msg = $json['message'] ?? $json['error'] ?? null;
            if (is_string($msg) && $msg !== '') {
                if ($status === 404 || str_contains(strtolower($msg), 'bucket')) {
                    return "Bucket no encontrado en Supabase. Verifique SUPABASE_BUCKET_JUSTIFICATIONS (actual: bucket configurado). Detalle: {$msg}";
                }

                return $msg;
            }
        }

        if ($status === 404) {
            return 'Bucket o ruta no encontrados en Supabase Storage (404).';
        }

        if ($status === 401 || $status === 403) {
            return 'Credenciales de Supabase inválidas o sin permiso de escritura (revise SUPABASE_SERVICE_KEY).';
        }

        return $body !== '' ? Str::limit($body, 200) : "Error de almacenamiento HTTP {$status}.";
    }
}
