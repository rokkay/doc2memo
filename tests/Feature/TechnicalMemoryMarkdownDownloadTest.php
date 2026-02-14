<?php

use App\Models\TechnicalMemory;
use App\Models\TechnicalMemorySection;
use App\Models\Tender;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('downloads the technical memory in markdown format', function (): void {
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

    $response = $this->get(route('technical-memories.download-markdown', $memory));

    $response->assertOk();
    $response->assertDownload('Memoria_Tecnica_REF-123.md');
    $response->assertHeader('content-type', 'text/markdown; charset=UTF-8');

    $content = $response->streamedContent();

    expect($content)->toContain('# Memoria Técnica - '.$tender->title);
    expect($content)->toContain('## 1.1 Introducción');
    expect($content)->toContain('Texto de introducción.');
    expect($content)->toContain('## 2.4 Enfoque Técnico');
    expect($content)->toContain('- Punto A');
});
