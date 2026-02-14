<?php

declare(strict_types=1);

use App\Ai\Agents\DocumentAnalyzer;
use App\Jobs\ProcessDocument;
use App\Models\Document;
use App\Models\Tender;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
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

    (new ProcessDocument($document))->handle();

    $processedDocument = $document->fresh();

    expect($processedDocument?->status)->toBe('analyzed')
        ->and((float) $processedDocument?->estimated_analysis_cost_usd)->toBeGreaterThan(0.0)
        ->and($processedDocument?->analysis_cost_breakdown)->toBeArray()
        ->and($processedDocument?->analysis_cost_breakdown)->toHaveKeys(['document_analyzer', 'dedicated_judgment_extractor'])
        ->and(data_get($processedDocument?->analysis_cost_breakdown, 'document_analyzer.status'))->toBe('completed')
        ->and(data_get($processedDocument?->analysis_cost_breakdown, 'dedicated_judgment_extractor.status'))->toBe('skipped');

    $analysisBreakdown = $processedDocument?->analysis_cost_breakdown;
    $analysisBreakdownTotal = round(
        (float) data_get($analysisBreakdown, 'document_analyzer.estimated_cost_usd', 0)
        + (float) data_get($analysisBreakdown, 'dedicated_judgment_extractor.estimated_cost_usd', 0),
        6,
    );

    expect((float) $processedDocument?->estimated_analysis_cost_usd)->toBe($analysisBreakdownTotal);

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

    (new ProcessDocument($document))->handle();

    $processedDocument = $document->fresh();

    expect($processedDocument?->status)->toBe('analyzed')
        ->and($processedDocument?->analysis_cost_breakdown)->toBeArray()
        ->and($processedDocument?->analysis_cost_breakdown)->toHaveKeys(['document_analyzer', 'dedicated_judgment_extractor']);

    $analysisBreakdown = $processedDocument?->analysis_cost_breakdown;
    $analysisBreakdownTotal = round(
        (float) data_get($analysisBreakdown, 'document_analyzer.estimated_cost_usd', 0)
        + (float) data_get($analysisBreakdown, 'dedicated_judgment_extractor.estimated_cost_usd', 0),
        6,
    );

    expect((float) $processedDocument?->estimated_analysis_cost_usd)->toBe($analysisBreakdownTotal);
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

    (new ProcessDocument($document))->handle();

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

    (new ProcessDocument($document))->handle();

    $storedDeadline = DB::table('tenders')->where('id', $tender->id)->value('deadline_date');

    expect($storedDeadline)->toBe('“decimoquinto dia desde la publicacion”');
});
