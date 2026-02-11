<?php

use App\Jobs\GenerateTechnicalMemory;
use App\Livewire\Tenders\TenderDetail;
use App\Models\Document;
use App\Models\ExtractedCriterion;
use App\Models\ExtractedSpecification;
use App\Models\TechnicalMemory;
use App\Models\Tender;
use App\Services\DocumentAnalysisService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Livewire\Livewire;

uses()->group('livewire');
uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->seed(\Database\Seeders\DatabaseSeeder::class);
});

it('renders successfully with tender', function (): void {
    $tender = Tender::factory()->create();

    Livewire::test(TenderDetail::class, ['tender' => $tender])
        ->assertOk();
});

it('displays tender information', function (): void {
    $tender = Tender::factory()->create([
        'title' => 'Test Tender Title',
        'issuing_company' => 'Test Company',
        'reference_number' => 'REF-123',
        'description' => 'Test description',
    ]);

    Livewire::test(TenderDetail::class, ['tender' => $tender])
        ->assertSee('Test Tender Title')
        ->assertSee('Test Company')
        ->assertSee('REF-123')
        ->assertSee('Test description');
});

it('displays textual deadline date without date formatting', function (): void {
    $tender = Tender::factory()->create([
        'deadline_date' => '15 dias naturales desde la publicacion',
    ]);

    Livewire::test(TenderDetail::class, ['tender' => $tender])
        ->assertSee('15 dias naturales desde la publicacion');
});

it('displays documents list', function (): void {
    $tender = Tender::factory()->create();
    Document::factory()->create([
        'tender_id' => $tender->id,
        'original_filename' => 'test-document.pdf',
        'document_type' => 'pca',
        'status' => 'uploaded',
    ]);

    Livewire::test(TenderDetail::class, ['tender' => $tender])
        ->assertSee('test-document.pdf')
        ->assertSee('PCA');
});

it('displays extracted criteria', function (): void {
    $tender = Tender::factory()->create();
    ExtractedCriterion::factory()->create([
        'tender_id' => $tender->id,
        'section_title' => 'Test Criterion',
        'description' => 'Test criterion description',
        'priority' => 'mandatory',
    ]);

    Livewire::test(TenderDetail::class, ['tender' => $tender])
        ->assertSee('Test Criterion')
        ->assertSee('Test criterion description')
        ->assertSee('Mandatory');
});

it('displays extracted specifications', function (): void {
    $tender = Tender::factory()->create();
    ExtractedSpecification::factory()->create([
        'tender_id' => $tender->id,
        'section_title' => 'Test Specification',
        'technical_description' => 'Test technical description',
    ]);

    Livewire::test(TenderDetail::class, ['tender' => $tender])
        ->assertSee('Test Specification')
        ->assertSee('Test technical description');
});

it('displays technical memory when available', function (): void {
    $tender = Tender::factory()->create(['status' => 'completed']);
    ExtractedCriterion::factory()->create(['tender_id' => $tender->id]);
    TechnicalMemory::factory()->create([
        'tender_id' => $tender->id,
        'title' => 'Test Technical Memory',
    ]);

    Livewire::test(TenderDetail::class, ['tender' => $tender])
        ->assertSee('Memoria Técnica Generada')
        ->assertSee('Test Technical Memory')
        ->assertSee('Ver Memoria Técnica');
});

it('shows per-document analyze actions and hides general analyze button', function (): void {
    $tender = Tender::factory()->create();
    Document::factory()->create([
        'tender_id' => $tender->id,
        'document_type' => 'pca',
        'status' => 'uploaded',
    ]);
    Document::factory()->create([
        'tender_id' => $tender->id,
        'document_type' => 'ppt',
        'status' => 'failed',
    ]);

    Livewire::test(TenderDetail::class, ['tender' => $tender])
        ->assertSee('Analizar')
        ->assertSee('Reintentar análisis')
        ->assertDontSee('Analizar Documentos');
});

it('can trigger document analysis for a specific document', function (): void {
    $tender = Tender::factory()->create();
    $document = Document::factory()->create([
        'tender_id' => $tender->id,
        'document_type' => 'pca',
        'status' => 'failed',
    ]);

    $documentAnalysisService = \Mockery::mock(DocumentAnalysisService::class);
    $documentAnalysisService
        ->shouldReceive('analyzeDocument')
        ->once()
        ->withArgs(fn (Tender $analyzedTender, Document $analyzedDocument): bool => $analyzedTender->is($tender) && $analyzedDocument->is($document));

    app()->instance(DocumentAnalysisService::class, $documentAnalysisService);

    Livewire::test(TenderDetail::class, ['tender' => $tender])
        ->call('retryDocument', $document->id)
        ->assertDispatched('analysis-completed');
});

it('marks document as processing immediately when retry starts', function (): void {
    Queue::fake();

    $tender = Tender::factory()->create();
    $document = Document::factory()->create([
        'tender_id' => $tender->id,
        'document_type' => 'pca',
        'status' => 'failed',
    ]);

    Livewire::test(TenderDetail::class, ['tender' => $tender])
        ->call('retryDocument', $document->id)
        ->assertDispatched('analysis-completed');

    expect($document->fresh()->status)->toBe('processing');
});

it('renders styled document action buttons for download and retry', function (): void {
    $tender = Tender::factory()->create();
    Document::factory()->create([
        'tender_id' => $tender->id,
        'document_type' => 'pca',
        'status' => 'failed',
    ]);

    Livewire::test(TenderDetail::class, ['tender' => $tender])
        ->assertSeeHtml('inline-flex items-center justify-center rounded-md')
        ->assertSeeHtml('bg-sky-100')
        ->assertSeeHtml('text-sky-800')
        ->assertSeeHtml('bg-red-100')
        ->assertSeeHtml('text-red-800');
});

it('queues technical memory generation when analysis is complete', function (): void {
    $tender = Tender::factory()->create(['status' => 'completed']);
    Queue::fake();

    Livewire::test(TenderDetail::class, ['tender' => $tender])
        ->call('generateMemory')
        ->assertDispatched('memory-generated');

    Queue::assertPushed(GenerateTechnicalMemory::class, function (GenerateTechnicalMemory $job) use ($tender): bool {
        return $job->tender->is($tender);
    });

    expect($tender->fresh()->technicalMemory)->not->toBeNull();
    expect($tender->fresh()->technicalMemory->status)->toBe('draft');
});
