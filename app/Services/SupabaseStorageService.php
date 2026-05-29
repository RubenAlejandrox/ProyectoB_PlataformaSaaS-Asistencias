<?php

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

    public function __construct()
    {
        $this->url = rtrim((string) config('services.supabase.url'), '/');
        $this->key = (string) config('services.supabase.service_key');
    }

    public function getLastError(): ?string
    {
        return $this->lastError;
    }

    public function isConfigured(): bool
    {
        return $this->url !== '' && $this->key !== '';
    }

    public function justificationsBucket(): string
    {
        $bucket = config('services.supabase.buckets.justifications');

        return ($bucket !== null && $bucket !== '') ? (string) $bucket : 'justificantes-adjuntos';
    }

    public function institutionLogosBucket(): string
    {
        $bucket = config('services.supabase.buckets.institution_logos');

        return ($bucket !== null && $bucket !== '') ? (string) $bucket : 'institution-logos';
    }

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
