<?php

namespace Tests\Unit;

use App\Services\SupabaseStorageService;
use Illuminate\Http\UploadedFile;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class SupabaseStorageServiceTest extends TestCase
{
    #[Test]
    public function default_justifications_bucket_is_justificantes_adjuntos(): void
    {
        config(['services.supabase.buckets.justifications' => null]);

        $service = new SupabaseStorageService;

        $this->assertSame('justificantes-adjuntos', $service->justificationsBucket());
    }

    #[Test]
    public function allows_pdf_with_octet_stream_mime(): void
    {
        $service = new SupabaseStorageService;
        $file    = UploadedFile::fake()->create('doc.pdf', 100, 'application/octet-stream');

        $this->assertTrue(
            $service->isAllowedMime($file, 'justificantes-adjuntos')
        );
    }
}
