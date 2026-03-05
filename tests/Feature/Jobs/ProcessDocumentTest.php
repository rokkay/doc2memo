<?php

declare(strict_types=1);

use App\Actions\Documents\AnalyzeDocumentWithMetricsAction;
use App\Ai\Agents\DocumentAnalyzer;
use App\Enums\AiCostCategory;
use App\Jobs\ProcessDocument;
use App\Listeners\RecordAiUsageFromAgentPrompted;
use App\Models\Document;
use App\Models\Tender;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

use function Pest\Laravel\assertDatabaseHas;

uses(RefreshDatabase::class);

it('processes a pca markdown document and stores extracted data', function (): void {
    Storage::fake('local');

    $tender = Tender::factory()->pending()->create();

    $filePath = 'documents/'.$tender->id.'/pca.md';
    Storage::disk('local')->put($filePath, "# PCA\n\nPlazo de presentacion: 2026-03-01");

    $document = Document::factory()->create([
        'tender_id' => $tender->id,
        'document_type' => 'pca',
        'file_path' => $filePath,
        'mime_type' => 'text/markdown',
        'status' => 'uploaded',
    ]);

    DocumentAnalyzer::fake([
        [
            'tender_info' => [
                'title' => 'Servicio Portal Web',
                'issuing_company' => 'Fundacion Cidade da Cultura',
                'reference_number' => 'CDC-2026-0003',
                'deadline_date' => '2026-03-01',
                'description' => 'Contrato de servicios de portal web',
            ],
            'criteria' => [
                [
                    'section_number' => 'A',
                    'section_title' => 'Presupuesto base',
                    'description' => 'Presupuesto maximo 251.559,00 EUR IVA incluido.',
                    'priority' => 'mandatory',
                    'metadata' => ['amount' => '251559'],
                ],
            ],
            'insights' => [
                [
                    'section_reference' => 'A.1',
                    'topic' => 'Presupuesto',
                    'requirement_type' => 'budget',
                    'importance' => 'high',
                    'statement' => 'La oferta no debe superar el presupuesto base.',
                    'evidence_excerpt' => 'Presupuesto base de licitacion con IVA: 251.559,00 EUR',
                ],
            ],
        ],
    ])->preventStrayPrompts();

    new ProcessDocument($document)->handle();

    $processedDocument = $document->fresh();

    expect($processedDocument?->status)->toBe('analyzed')
        ->and($processedDocument?->analyzed_at)->not->toBeNull();

    $aiEntryCostTotal = round((float) DB::table('ai_cost_entries')
        ->where('document_id', $document->id)
        ->sum('estimated_cost_usd'), 6);

    expect($aiEntryCostTotal)->toBeGreaterThan(0.0);

    assertDatabaseHas('ai_cost_entries', [
        'document_id' => $document->id,
        'tender_id' => $tender->id,
        'category' => AiCostCategory::DocumentAnalyzer->value,
        'agent_key' => 'document_analyzer',
        'status' => 'completed',
    ]);

    assertDatabaseHas('ai_cost_entries', [
        'document_id' => $document->id,
        'tender_id' => $tender->id,
        'category' => AiCostCategory::DedicatedJudgmentExtractor->value,
        'agent_key' => 'dedicated_judgment_extractor',
    ]);

    assertDatabaseHas('extracted_criteria', [
        'document_id' => $document->id,
        'section_title' => 'Presupuesto base',
    ]);

    assertDatabaseHas('document_insights', [
        'document_id' => $document->id,
        'topic' => 'Presupuesto',
        'requirement_type' => 'budget',
    ]);
});

it('stores textual deadline date extracted by ai', function (): void {
    Storage::fake('local');

    $tender = Tender::factory()->pending()->create();

    $filePath = 'documents/'.$tender->id.'/pca.md';
    Storage::disk('local')->put($filePath, "# PCA\n\nFecha limite en texto natural");

    $document = Document::factory()->create([
        'tender_id' => $tender->id,
        'document_type' => 'pca',
        'file_path' => $filePath,
        'mime_type' => 'text/markdown',
        'status' => 'uploaded',
    ]);

    DocumentAnalyzer::fake([
        [
            'tender_info' => [
                'title' => 'Servicio Portal Web',
                'issuing_company' => 'Fundacion Cidade da Cultura',
                'reference_number' => 'CDC-2026-0003',
                'deadline_date' => '“decimoquinto dia, contado desde el dia siguiente al de la publicacion del anuncio...”',
                'description' => 'Contrato de servicios de portal web',
            ],
            'criteria' => [],
            'insights' => [],
        ],
    ])->preventStrayPrompts();

    new ProcessDocument($document)->handle();

    $processedDocument = $document->fresh();

    expect($processedDocument?->status)->toBe('analyzed')
        ->and($processedDocument?->analyzed_at)->not->toBeNull();

    expect(DB::table('ai_cost_entries')->where('document_id', $document->id)->count())->toBe(2);
    expect($tender->fresh()->deadline_date)->toBe('“decimoquinto dia, contado desde el dia siguiente al de la publicacion del anuncio...”');
});

it('analyzes large documents in a single full-document pass', function (): void {
    Storage::fake('local');

    $tender = Tender::factory()->pending()->create();
    $filePath = 'documents/'.$tender->id.'/pca.md';
    Storage::disk('local')->put($filePath, str_repeat("Bloque grande de texto\n\n", 80));

    $document = Document::factory()->create([
        'tender_id' => $tender->id,
        'document_type' => 'pca',
        'file_path' => $filePath,
        'mime_type' => 'text/markdown',
        'status' => 'uploaded',
    ]);

    DocumentAnalyzer::fake([
        [
            'tender_info' => [
                'title' => 'Servicio Portal Web',
                'issuing_company' => 'Fundacion Cidade da Cultura',
                'reference_number' => 'CDC-2026-0003',
                'deadline_date' => '15 dias desde la publicacion',
                'description' => 'Contrato de servicios',
            ],
            'criteria' => [[
                'section_number' => 'A',
                'section_title' => 'Presupuesto base',
                'description' => 'Presupuesto maximo.',
                'priority' => 'mandatory',
                'metadata' => [],
            ]],
            'insights' => [],
        ],
    ])->preventStrayPrompts();

    new ProcessDocument($document)->handle();

    expect($document->fresh()->status)->toBe('analyzed');
    expect($document->fresh()->extractedCriteria()->count())->toBe(1);
});

it('stores textual deadline without relying on model date casts', function (): void {
    Storage::fake('local');

    $tender = Tender::factory()->pending()->create();

    $filePath = 'documents/'.$tender->id.'/pca.md';
    Storage::disk('local')->put($filePath, "# PCA\n\nPlazo textual");

    $document = Document::factory()->create([
        'tender_id' => $tender->id,
        'document_type' => 'pca',
        'file_path' => $filePath,
        'mime_type' => 'text/markdown',
        'status' => 'uploaded',
    ]);

    $legacyCastTender = $tender->mergeCasts([
        'deadline_date' => 'date',
    ]);
    $document->setRelation('tender', $legacyCastTender);

    DocumentAnalyzer::fake([
        [
            'tender_info' => [
                'title' => 'Servicio Portal Web',
                'issuing_company' => 'Fundacion Cidade da Cultura',
                'reference_number' => 'CDC-2026-0003',
                'deadline_date' => '“decimoquinto dia desde la publicacion”',
                'description' => 'Contrato de servicios de portal web',
            ],
            'criteria' => [],
            'insights' => [],
        ],
    ])->preventStrayPrompts();

    new ProcessDocument($document)->handle();

    $storedDeadline = DB::table('tenders')->where('id', $tender->id)->value('deadline_date');

    expect($storedDeadline)->toBe('“decimoquinto dia desde la publicacion”');
});

it('delegates document analysis and keeps persisted analysis cost breakdown keys', function (): void {
    Storage::fake('local');

    $tender = Tender::factory()->pending()->create();
    $filePath = 'documents/'.$tender->id.'/pca.md';
    Storage::disk('local')->put($filePath, '# PCA');

    $document = Document::factory()->create([
        'tender_id' => $tender->id,
        'document_type' => 'pca',
        'file_path' => $filePath,
        'mime_type' => 'text/markdown',
        'status' => 'uploaded',
    ]);

    DocumentAnalyzer::fake([
        [
            'tender_info' => [
                'title' => 'Servicio Portal Web',
                'issuing_company' => 'Entidad convocante',
                'reference_number' => 'EXP-2026-01',
                'deadline_date' => '15 dias',
                'description' => 'Contrato de servicios',
            ],
            'criteria' => [],
            'insights' => [],
        ],
    ])->preventStrayPrompts();

    $delegate = new class
    {
        public bool $called = false;

        /**
         * @return array{analysis:array<string,mixed>,costSummary:array{estimated_input_units:float,estimated_output_units:float,estimated_cost_usd:float,breakdown:array<string,array<string,int|float|string>>},dedicatedCriteria:array<int,mixed>}
         */
        public function __invoke(Document $document, string $text): array
        {
            $this->called = true;

            return [
                'analysis' => [
                    'tender_info' => [
                        'title' => 'Servicio Portal Web',
                        'issuing_company' => 'Entidad convocante',
                        'reference_number' => 'EXP-2026-01',
                        'deadline_date' => '15 dias',
                        'description' => 'Contrato de servicios',
                    ],
                    'criteria' => [],
                    'insights' => [],
                ],
                'costSummary' => [
                    'estimated_input_units' => 0.002,
                    'estimated_output_units' => 0.001,
                    'estimated_cost_usd' => 0.003,
                    'breakdown' => [
                        'document_analyzer' => [
                            'model_name' => 'gpt-5.2',
                            'input_chars' => 100,
                            'output_chars' => 100,
                            'estimated_input_units' => 0.001,
                            'estimated_output_units' => 0.0005,
                            'estimated_cost_usd' => 0.0015,
                            'status' => 'completed',
                        ],
                        'dedicated_judgment_extractor' => [
                            'model_name' => 'gpt-5-mini',
                            'input_chars' => 0,
                            'output_chars' => 0,
                            'estimated_input_units' => 0.001,
                            'estimated_output_units' => 0.0005,
                            'estimated_cost_usd' => 0.0015,
                            'status' => 'skipped',
                        ],
                    ],
                ],
                'dedicatedCriteria' => [],
            ];
        }
    };

    app()->instance(AnalyzeDocumentWithMetricsAction::class, $delegate);

    new ProcessDocument($document)->handle();

    $processedDocument = $document->fresh();

    expect($delegate->called)->toBeTrue()
        ->and($processedDocument?->status)->toBe('analyzed')
        ->and(DB::table('ai_cost_entries')->where('document_id', $document->id)->count())->toBe(2);
});

it('stores analyzer token usage metadata and char fallback for document analysis', function (): void {
    RecordAiUsageFromAgentPrompted::flush();

    Storage::fake('local');

    $tender = Tender::factory()->pending()->create();
    $filePath = 'documents/'.$tender->id.'/pca.md';
    Storage::disk('local')->put($filePath, '# PCA');

    $document = Document::factory()->create([
        'tender_id' => $tender->id,
        'document_type' => 'pca',
        'file_path' => $filePath,
        'mime_type' => 'text/markdown',
        'status' => 'uploaded',
    ]);

    RecordAiUsageFromAgentPrompted::recordUsageForAgent(DocumentAnalyzer::class, [
        'prompt_tokens' => 210,
        'completion_tokens' => 90,
    ]);

    DocumentAnalyzer::fake([
        [
            'tender_info' => [
                'title' => 'Servicio Portal Web',
                'issuing_company' => 'Entidad convocante',
                'reference_number' => 'EXP-2026-01',
                'deadline_date' => '15 dias',
                'description' => 'Contrato de servicios',
            ],
            'criteria' => [],
            'insights' => [],
        ],
    ])->preventStrayPrompts();

    new ProcessDocument($document)->handle();

    $analyzerEntry = DB::table('ai_cost_entries')
        ->where('document_id', $document->id)
        ->where('agent_key', 'document_analyzer')
        ->first();

    $extractorEntry = DB::table('ai_cost_entries')
        ->where('document_id', $document->id)
        ->where('agent_key', 'dedicated_judgment_extractor')
        ->first();

    expect($analyzerEntry)->not->toBeNull()
        ->and($extractorEntry)->not->toBeNull()
        ->and((int) ($analyzerEntry?->prompt_tokens ?? 0))->toBeGreaterThanOrEqual(0)
        ->and((int) ($analyzerEntry?->completion_tokens ?? 0))->toBeGreaterThanOrEqual(0)
        ->and((int) ($extractorEntry?->prompt_tokens ?? 0))->toBe(0)
        ->and((int) ($analyzerEntry?->input_chars ?? 0))->toBeGreaterThan(0)
        ->and((int) ($extractorEntry?->input_chars ?? 0))->toBe(0);

    RecordAiUsageFromAgentPrompted::flush();
});

it('marks document and tender as failed when processing throws', function (): void {
    Log::spy();

    $tender = Tender::factory()->create(['status' => 'analyzing']);
    $document = Document::factory()->create([
        'tender_id' => $tender->id,
        'status' => 'processing',
        'file_path' => 'documents/missing.md',
        'mime_type' => 'text/markdown',
    ]);

    expect(fn (): mixed => new ProcessDocument($document)->handle())
        ->toThrow(\ErrorException::class);

    expect($document->fresh()->status)->toBe('failed')
        ->and($document->fresh()->processing_error)->not->toBeNull()
        ->and($document->fresh()->extracted_text)->toContain('Error:')
        ->and($tender->fresh()->status)->toBe('failed');

    Log::shouldHaveReceived('error')
        ->once();
});
