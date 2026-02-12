<?php

use App\Models\TechnicalMemory;
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
        'introduction' => "### Contexto\n\nTexto de introducción.",
        'technical_approach' => "### Enfoque\n\n- Punto A\n- Punto B",
    ]);

    $response = $this->get(route('technical-memories.download-markdown', $memory));

    $response->assertOk();
    $response->assertDownload('Memoria_Tecnica_REF-123.md');
    $response->assertHeader('content-type', 'text/markdown; charset=UTF-8');

    $content = $response->streamedContent();

    expect($content)->toContain('# Memoria Técnica - '.$tender->title);
    expect($content)->toContain('## Introducción');
    expect($content)->toContain('Texto de introducción.');
    expect($content)->toContain('## Enfoque Técnico');
    expect($content)->toContain('- Punto A');
});
