<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class SupabaseStorageService
{
    private string $url;
    private string $key;

    public function __construct()
    {
        $this->url = rtrim(config('services.supabase.url'), '/');
        $this->key = config('services.supabase.service_key');
    }

    public function upload(UploadedFile $file, string $bucket, string $folder = ''): ?string
    {
        $extension = $file->getClientOriginalExtension();
        $filename  = $folder
            ? "{$folder}/" . Str::uuid() . ".{$extension}"
            : Str::uuid() . ".{$extension}";

        $response = Http::withHeaders([
                'Authorization' => "Bearer {$this->key}",
                'Content-Type'  => $file->getMimeType(),
            ])
            ->when(app()->environment('local'), fn($http) => $http->withoutVerifying())
            ->withBody(
                file_get_contents($file->getRealPath()),
                $file->getMimeType()
            )
            ->post("{$this->url}/storage/v1/object/{$bucket}/{$filename}");

        if ($response->successful()) {
    return "{$this->url}/storage/v1/object/public/{$bucket}/{$filename}";
}

        return null;
    }

    public function delete(string $bucket, string $url): bool
    {
        $path = str_replace(
            "{$this->url}/storage/v1/object/sign/{$bucket}/",
            '',
            $url
        );

        $response = Http::withHeaders([
                'Authorization' => "Bearer {$this->key}",
            ])
            ->when(app()->environment('local'), fn($http) => $http->withoutVerifying())
            ->delete("{$this->url}/storage/v1/object/{$bucket}", [
                'prefixes' => [$path],
            ]);

        return $response->successful();
    }

    public function getPublicUrl(string $bucket, string $path): string
    {
        return "{$this->url}/storage/v1/object/public/{$bucket}/{$path}";
    }
}