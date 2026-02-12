<?php

use App\Models\TechnicalMemory;
use App\Models\Tender;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\LaravelPdf\Facades\Pdf;
use Spatie\LaravelPdf\PdfBuilder;

uses(RefreshDatabase::class);

it('downloads the technical memory in pdf format', function (): void {
    Pdf::fake();

    $tender = Tender::factory()->create([
        'reference_number' => 'REF-123',
    ]);

    $memory = TechnicalMemory::factory()->create([
        'tender_id' => $tender->id,
        'title' => 'Memoria Técnica - '.$tender->title,
        'introduction' => "### Contexto\n\nTexto de introducción.",
        'technical_approach' => "### Enfoque\n\n- Punto A\n- Punto B",
    ]);

    $response = $this->get(route('technical-memories.download', $memory));

    $response->assertSuccessful();

    Pdf::assertRespondedWithPdf(function (PdfBuilder $pdf): bool {
        return $pdf->isDownload()
            && $pdf->downloadName === 'Memoria_Tecnica_REF-123.pdf'
            && $pdf->viewName === 'technical-memories.pdf'
            && $pdf->headerViewName === 'technical-memories.pdf-header'
            && $pdf->footerViewName === 'technical-memories.pdf-footer'
            && $pdf->format === 'a4'
            && $pdf->margins === [
                'top' => 24.0,
                'right' => 14.0,
                'bottom' => 20.0,
                'left' => 14.0,
                'unit' => 'mm',
            ]
            && $pdf->contains(['Documento técnico', '## Introducción', 'Página @pageNumber de @totalPages']);
    });
});
