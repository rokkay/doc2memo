<?php

use App\Livewire\Documents\DocumentDetail;
use App\Models\Document;
use App\Models\ExtractedCriterion;
use App\Models\ExtractedSpecification;
use App\Models\Tender;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses()->group('livewire');
uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->seed(\Database\Seeders\DatabaseSeeder::class);
});

it('renders successfully', function (): void {
    $document = Document::factory()->create();

    Livewire::test(DocumentDetail::class, ['document' => $document])
        ->assertOk();
});

it('displays document info', function (): void {
    $tender = Tender::factory()->create();
    $document = Document::factory()->create([
        'tender_id' => $tender->id,
        'document_type' => 'pca',
        'original_filename' => 'pliego-pca.pdf',
        'file_size' => 2048,
        'status' => 'analyzed',
    ]);

    Livewire::test(DocumentDetail::class, ['document' => $document])
        ->assertSee('pliego-pca.pdf')
        ->assertSee('Pliego de Condiciones Administrativas')
        ->assertSee('2.00 KB')
        ->assertSee('Analizado');
});

it('displays extracted text when available', function (): void {
    $document = Document::factory()->create([
        'extracted_text' => 'Este es el texto extraido del documento.',
    ]);

    Livewire::test(DocumentDetail::class, ['document' => $document])
        ->assertSee('Texto extraido')
        ->assertSee('Mostrar contenido completo')
        ->assertSeeHtml('<details class="group')
        ->assertSee('Este es el texto extraido del documento.');
});

it('displays extracted criteria for pca documents', function (): void {
    $tender = Tender::factory()->create();
    $document = Document::factory()->create([
        'tender_id' => $tender->id,
        'document_type' => 'pca',
    ]);

    ExtractedCriterion::factory()->create([
        'tender_id' => $tender->id,
        'document_id' => $document->id,
        'section_title' => 'Capacidad tecnica',
        'description' => 'Debe aportar experiencia en proyectos similares.',
        'priority' => 'mandatory',
    ]);

    Livewire::test(DocumentDetail::class, ['document' => $document])
        ->assertSee('Criterios extraidos (PCA)')
        ->assertSee('Capacidad tecnica')
        ->assertSee('Debe aportar experiencia en proyectos similares.')
        ->assertSeeHtml('xl:grid-cols-2')
        ->assertSee('Mandatory');
});

it('displays extracted specifications for ppt documents', function (): void {
    $tender = Tender::factory()->create();
    $document = Document::factory()->create([
        'tender_id' => $tender->id,
        'document_type' => 'ppt',
    ]);

    ExtractedSpecification::factory()->create([
        'tender_id' => $tender->id,
        'document_id' => $document->id,
        'section_title' => 'Arquitectura de la solucion',
        'technical_description' => 'Se requiere arquitectura escalable basada en microservicios.',
        'requirements' => 'Compatibilidad con API REST.',
    ]);

    Livewire::test(DocumentDetail::class, ['document' => $document])
        ->assertSee('Especificaciones extraidas (PPT)')
        ->assertSee('Arquitectura de la solucion')
        ->assertSee('Se requiere arquitectura escalable basada en microservicios.')
        ->assertSee('Compatibilidad con API REST.');
});

it('has back link and download link labels', function (): void {
    $document = Document::factory()->create();

    Livewire::test(DocumentDetail::class, ['document' => $document])
        ->assertSee('Volver')
        ->assertSee('Descargar');
});
