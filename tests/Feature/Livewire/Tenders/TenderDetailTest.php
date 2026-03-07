<?php

use App\Ai\Agents\TechnicalMemoryDynamicSectionAgent;
use App\Livewire\Tenders\TenderDetail;
use App\Models\Document;
use App\Models\ExtractedCriterion;
use App\Models\ExtractedSpecification;
use App\Models\TechnicalMemory;
use App\Models\TechnicalMemorySection;
use App\Models\Tender;
use App\Services\DocumentAnalysisService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Laravel\Ai\QueuedAgentPrompt;
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
        ->assertSee('Obligatorio');
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
        'status' => 'generated',
    ]);

    Livewire::test(TenderDetail::class, ['tender' => $tender])
        ->assertSee('Memoria Técnica Generada')
        ->assertSee('Test Technical Memory')
        ->assertSee('Ver Memoria Técnica')
        ->assertSee('Regenerar Memoria Técnica');
});

it('can regenerate existing technical memory and reset sections', function (): void {
    Bus::fake();

    $tender = Tender::factory()->create(['status' => 'completed']);
    $pcaDocument = Document::factory()->create([
        'tender_id' => $tender->id,
        'document_type' => 'pca',
    ]);
    $pptDocument = Document::factory()->create([
        'tender_id' => $tender->id,
        'document_type' => 'ppt',
    ]);

    ExtractedCriterion::factory()->create([
        'tender_id' => $tender->id,
        'document_id' => $pcaDocument->id,
        'criterion_type' => 'judgment',
        'source' => 'dedicated_extractor',
        'section_number' => '1.1',
        'section_title' => 'Metodología',
        'group_key' => '1.1-metodologia',
        'score_points' => 10,
    ]);

    ExtractedSpecification::factory()->create([
        'tender_id' => $tender->id,
        'document_id' => $pptDocument->id,
    ]);

    $memory = TechnicalMemory::factory()->create([
        'tender_id' => $tender->id,
        'status' => 'generated',
        'generated_at' => now(),
        'generated_file_path' => 'technical-memories/old.pdf',
    ]);

    TechnicalMemoryDynamicSectionAgent::fake()->preventStrayPrompts();

    Livewire::test(TenderDetail::class, ['tender' => $tender])
        ->call('generateMemory')
        ->assertDispatched('memory-generated');

    TechnicalMemoryDynamicSectionAgent::assertQueued(fn (QueuedAgentPrompt $prompt): bool => $prompt->agent instanceof TechnicalMemoryDynamicSectionAgent);

    $memory = $memory->fresh();

    expect($memory)->not->toBeNull();
    expect($memory?->status)->toBe('draft');
    expect($memory?->generated_at)->toBeNull();
    expect($memory?->generated_file_path)->toBeNull();
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
        ->assertSeeHtml('inline-flex items-center justify-center rounded-lg')
        ->assertSeeHtml('bg-sky-100')
        ->assertSeeHtml('text-sky-800')
        ->assertSeeHtml('bg-red-100')
        ->assertSeeHtml('text-red-800');
});

it('queues technical memory generation when analysis is complete', function (): void {
    $tender = Tender::factory()->create(['status' => 'completed']);
    $pcaDocument = Document::factory()->create([
        'tender_id' => $tender->id,
        'document_type' => 'pca',
    ]);
    $pptDocument = Document::factory()->create([
        'tender_id' => $tender->id,
        'document_type' => 'ppt',
    ]);

    ExtractedCriterion::factory()->create([
        'tender_id' => $tender->id,
        'document_id' => $pcaDocument->id,
        'criterion_type' => 'judgment',
        'source' => 'dedicated_extractor',
        'section_number' => '1.1',
        'section_title' => 'Metodología',
        'group_key' => '1.1-metodologia',
        'score_points' => 10,
    ]);

    ExtractedSpecification::factory()->create([
        'tender_id' => $tender->id,
        'document_id' => $pptDocument->id,
    ]);

    TechnicalMemoryDynamicSectionAgent::fake()->preventStrayPrompts();

    Livewire::test(TenderDetail::class, ['tender' => $tender])
        ->call('generateMemory')
        ->assertDispatched('memory-generated')
        ->assertSee('Ver progreso de la memoria')
        ->assertSee(route('technical-memories.show', $tender));

    TechnicalMemoryDynamicSectionAgent::assertQueued(fn (QueuedAgentPrompt $prompt): bool => $prompt->agent instanceof TechnicalMemoryDynamicSectionAgent);

    expect($tender->fresh()->technicalMemory)->not->toBeNull();
    expect($tender->fresh()->technicalMemory->status)->toBe('draft');
});

it('shows clear memory generation states in actions panel', function (): void {
    $tender = Tender::factory()->create(['status' => 'completed']);
    $memory = TechnicalMemory::factory()->create([
        'tender_id' => $tender->id,
        'status' => 'draft',
    ]);

    TechnicalMemorySection::factory()->create([
        'technical_memory_id' => $memory->id,
        'section_title' => 'Seccion pendiente',
        'status' => 'pending',
        'sort_order' => 1,
    ]);

    TechnicalMemorySection::factory()->create([
        'technical_memory_id' => $memory->id,
        'section_title' => 'Seccion en redaccion',
        'status' => 'generating',
        'sort_order' => 2,
    ]);

    TechnicalMemorySection::factory()->create([
        'technical_memory_id' => $memory->id,
        'section_title' => 'Seccion completada',
        'status' => 'completed',
        'sort_order' => 3,
    ]);

    Livewire::test(TenderDetail::class, ['tender' => $tender])
        ->assertSee('En cola')
        ->assertSee('En curso')
        ->assertSee('Completadas')
        ->assertSee('Error')
        ->assertSee('Ahora en curso')
        ->assertSee('Seccion pendiente')
        ->assertSee('Seccion en redaccion')
        ->assertSee('1/3 secciones completadas.');
});

it('returns empty memory progress when no technical memory exists', function (): void {
    $tender = Tender::factory()->create();

    Livewire::test(TenderDetail::class, ['tender' => $tender])
        ->assertSet('memoryProgress.has_sections', false)
        ->assertSet('memoryProgress.total_count', 0)
        ->assertSet('memoryProgress.pending_count', 0)
        ->assertSet('memoryProgress.generating_count', 0);
});

it('handles analyze documents service exceptions', function (): void {
    $tender = Tender::factory()->create();

    $documentAnalysisService = new class extends DocumentAnalysisService
    {
        public function __construct() {}

        public function analyzeTender(Tender $tender): void
        {
            throw new RuntimeException('analysis failed');
        }
    };

    app()->instance(DocumentAnalysisService::class, $documentAnalysisService);

    Livewire::test(TenderDetail::class, ['tender' => $tender])
        ->call('analyzeDocuments')
        ->assertSet('isAnalyzing', false)
        ->assertSet('errorMessage', 'Error al analizar los documentos: analysis failed');
});

it('sets error when retrying a document that is not found', function (): void {
    $tender = Tender::factory()->create();

    Livewire::test(TenderDetail::class, ['tender' => $tender])
        ->call('retryDocument', 9999)
        ->assertSet('isAnalyzing', false)
        ->assertSet('errorMessage', 'Documento no encontrado para reintento.');
});

it('handles retry document service exceptions', function (): void {
    $tender = Tender::factory()->create();
    $document = Document::factory()->create([
        'tender_id' => $tender->id,
        'status' => 'failed',
    ]);

    $documentAnalysisService = new class extends DocumentAnalysisService
    {
        public function __construct() {}

        public function analyzeDocument(Tender $tender, Document $document): void
        {
            throw new RuntimeException('retry failed');
        }
    };

    app()->instance(DocumentAnalysisService::class, $documentAnalysisService);

    Livewire::test(TenderDetail::class, ['tender' => $tender])
        ->call('retryDocument', $document->id)
        ->assertSet('isAnalyzing', false)
        ->assertSet('errorMessage', 'Error al reintentar el análisis: retry failed');
});

it('handles memory generation service exceptions', function (): void {
    $tender = Tender::factory()->create(['status' => 'completed']);

    $generationService = new class extends \App\Services\TechnicalMemoryGenerationService
    {
        public function __construct() {}

        public function generate(Tender $tender): TechnicalMemory
        {
            throw new RuntimeException('generation failed');
        }
    };

    app()->instance(\App\Services\TechnicalMemoryGenerationService::class, $generationService);

    Livewire::test(TenderDetail::class, ['tender' => $tender])
        ->call('generateMemory')
        ->assertSet('isGeneratingMemory', false)
        ->assertSet('errorMessage', 'Error al generar la memoria técnica: generation failed');
});

it('analyzes documents successfully and dispatches completion event', function (): void {
    $tender = Tender::factory()->create();

    Document::factory()->create([
        'tender_id' => $tender->id,
        'status' => 'uploaded',
    ]);

    $documentAnalysisService = new class extends DocumentAnalysisService
    {
        public function __construct() {}

        public function analyzeTender(Tender $tender): void {}
    };

    app()->instance(DocumentAnalysisService::class, $documentAnalysisService);

    Livewire::test(TenderDetail::class, ['tender' => $tender])
        ->assertSet('hasAnalyzableDocuments', true)
        ->call('analyzeDocuments')
        ->assertSet('isAnalyzing', false)
        ->assertDispatched('analysis-completed');
});
