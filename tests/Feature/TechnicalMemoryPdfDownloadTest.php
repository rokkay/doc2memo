<?php

use App\Models\TechnicalMemory;
use App\Models\TechnicalMemorySection;
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
    ]);

    TechnicalMemorySection::factory()->create([
        'technical_memory_id' => $memory->id,
        'section_number' => '1.1',
        'section_title' => 'Introducción',
        'sort_order' => 1,
        'content' => "### Contexto\n\nTexto de introducción.",
        'status' => 'completed',
    ]);

    TechnicalMemorySection::factory()->create([
        'technical_memory_id' => $memory->id,
        'section_number' => '2.4',
        'section_title' => 'Enfoque Técnico',
        'sort_order' => 2,
        'content' => "### Enfoque\n\n- Punto A\n- Punto B",
        'status' => 'completed',
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
            && $pdf->contains(['Documento técnico', '## 1.1 Introducción', 'Página @pageNumber de @totalPages']);
    });
});
