<?php

declare(strict_types=1);

use App\Actions\Documents\ProcessDocumentAction;
use App\Ai\Agents\DocumentAnalyzer;
use App\Ai\Agents\PcaJudgmentCriteriaExtractorAgent;
use App\Models\Document;
use App\Models\Tender;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;

use function Pest\Laravel\assertDatabaseHas;

uses(RefreshDatabase::class);

it('stores criterion type, score points, and group key from analyzer payload', function (): void {
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
            'criteria' => [
                [
                    'section_number' => '1.2',
                    'section_title' => 'Metodología',
                    'description' => 'Plan de trabajo detallado.',
                    'priority' => 'mandatory',
                    'criterion_type' => 'judgment',
                    'score_points' => 35,
                    'metadata' => [],
                ],
            ],
            'insights' => [],
        ],
    ])->preventStrayPrompts();

    (new ProcessDocumentAction)($document);

    $processedDocument = $document->fresh();

    expect($processedDocument?->analysis_cost_breakdown)
        ->toBeArray()
        ->toHaveKeys(['document_analyzer', 'dedicated_judgment_extractor']);

    assertDatabaseHas('extracted_criteria', [
        'document_id' => $document->id,
        'section_title' => 'Metodología',
        'criterion_type' => 'judgment',
        'score_points' => 35.00,
        'group_key' => '1.2-metodologia',
        'source' => 'analyzer',
        'source_reference' => '1.2 Metodología',
    ]);
});

it('uses dedicated judgment criteria agent to extract over-b scoring table', function (): void {
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
            'criteria' => [
                [
                    'section_number' => 'Criterios B (juicio de valor)',
                    'section_title' => 'Requisitos de contenido de la oferta técnica (Sobre B)',
                    'description' => 'Bloque agregado con criterios de juicio de valor.',
                    'priority' => 'mandatory',
                    'criterion_type' => 'judgment',
                    'score_points' => '50',
                    'metadata' => [],
                ],
                [
                    'section_number' => 'M',
                    'section_title' => 'Condiciones especiales de ejecución',
                    'description' => 'Obligaciones legales de ejecución del contrato.',
                    'priority' => 'mandatory',
                    'criterion_type' => 'automatic',
                    'score_points' => '',
                    'metadata' => [],
                ],
            ],
            'insights' => [],
        ],
    ])->preventStrayPrompts();

    PcaJudgmentCriteriaExtractorAgent::fake([
        [
            'criteria' => [
                [
                    'section_number' => '1.1',
                    'section_title' => 'Propuesta de Evolución Funcional',
                    'description' => 'Detalle funcional evaluable',
                    'priority' => 'mandatory',
                    'score_points' => '16',
                    'metadata' => [],
                ],
                [
                    'section_number' => '1.2',
                    'section_title' => 'Propuesta de Evolución Tecnológica',
                    'description' => 'Detalle tecnológico evaluable',
                    'priority' => 'mandatory',
                    'score_points' => '10',
                    'metadata' => [],
                ],
                [
                    'section_number' => '2.4',
                    'section_title' => 'Mecanismos de Seguimiento y Control',
                    'description' => 'Seguimiento y control evaluable',
                    'priority' => 'mandatory',
                    'score_points' => '4',
                    'metadata' => [],
                ],
            ],
        ],
    ])->preventStrayPrompts();

    (new ProcessDocumentAction)($document);

    $processedDocument = $document->fresh();

    expect($processedDocument)->not->toBeNull()
        ->and((float) $processedDocument?->estimated_analysis_cost_usd)->toBeGreaterThan(0.0)
        ->and(data_get($processedDocument?->analysis_cost_breakdown, 'document_analyzer.status'))->toBe('completed')
        ->and(data_get($processedDocument?->analysis_cost_breakdown, 'dedicated_judgment_extractor.status'))->toBe('completed')
        ->and((float) data_get($processedDocument?->analysis_cost_breakdown, 'dedicated_judgment_extractor.estimated_cost_usd'))->toBeGreaterThan(0.0);

    $judgmentCount = \App\Models\ExtractedCriterion::query()
        ->where('document_id', $document->id)
        ->where('criterion_type', 'judgment')
        ->count();

    expect($judgmentCount)->toBe(4);

    assertDatabaseHas('extracted_criteria', [
        'document_id' => $document->id,
        'section_number' => '1.1',
        'section_title' => 'Propuesta de Evolución Funcional',
        'score_points' => 16.00,
        'source' => 'dedicated_extractor',
    ]);

    assertDatabaseHas('extracted_criteria', [
        'document_id' => $document->id,
        'section_number' => 'M',
        'criterion_type' => 'automatic',
    ]);
});

it('extracts score points from description when analyzer omits numeric score', function (): void {
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
            'criteria' => [
                [
                    'section_number' => '3.1',
                    'section_title' => 'Gobierno del servicio',
                    'description' => 'Hasta 18 puntos por modelo de seguimiento operativo.',
                    'priority' => 'preferable',
                    'criterion_type' => 'judgment',
                    'score_points' => null,
                    'metadata' => [],
                ],
            ],
            'insights' => [],
        ],
    ])->preventStrayPrompts();

    (new ProcessDocumentAction)($document);

    assertDatabaseHas('extracted_criteria', [
        'document_id' => $document->id,
        'section_title' => 'Gobierno del servicio',
        'criterion_type' => 'judgment',
        'score_points' => 18.00,
        'group_key' => '3.1-gobierno-del-servicio',
    ]);
});

it('normalizes noisy judgment prefixes when building group keys', function (): void {
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
            'criteria' => [
                [
                    'section_number' => '4.5',
                    'section_title' => 'Criterios adjudicación (B) Juicio de valor - Plan de calidad',
                    'description' => 'Hasta 12 puntos por sistema de calidad.',
                    'priority' => 'mandatory',
                    'criterion_type' => 'judgment',
                    'score_points' => '12',
                    'metadata' => [],
                ],
            ],
            'insights' => [],
        ],
    ])->preventStrayPrompts();

    (new ProcessDocumentAction)($document);

    assertDatabaseHas('extracted_criteria', [
        'document_id' => $document->id,
        'group_key' => '4.5-plan-de-calidad',
    ]);
});

it('expands grouped judgment criteria into multiple scored subcriteria', function (): void {
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
            'criteria' => [
                [
                    'section_number' => 'Cuadro criterios adjudicación',
                    'section_title' => 'Criterios de adjudicación (100 puntos)',
                    'description' => 'Juicio de valor 50: 1.1 evolución funcional 16; 1.2 tecnológica 10; 1.3 plan ejecución 4; 2.1 metodología 6; 2.2 organización del equipo 8; 2.4 seguimiento y control 4.',
                    'priority' => 'mandatory',
                    'criterion_type' => 'judgment',
                    'score_points' => '50',
                    'metadata' => [],
                ],
            ],
            'insights' => [],
        ],
    ])->preventStrayPrompts();

    (new ProcessDocumentAction)($document);

    $subcriteriaCount = \App\Models\ExtractedCriterion::query()
        ->where('document_id', $document->id)
        ->where('criterion_type', 'judgment')
        ->count();

    expect($subcriteriaCount)->toBe(6);

    assertDatabaseHas('extracted_criteria', [
        'document_id' => $document->id,
        'section_number' => '1.1',
        'section_title' => 'Evolución Funcional',
        'score_points' => 16.00,
    ]);

    assertDatabaseHas('extracted_criteria', [
        'document_id' => $document->id,
        'section_number' => '2.2',
        'section_title' => 'Organización Del Equipo',
        'score_points' => 8.00,
    ]);
});

it('expands descriptive judgment criteria into canonical subcriteria when numbering is absent', function (): void {
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
            'criteria' => [
                [
                    'section_number' => 'Criterios B (juicio de valor)',
                    'section_title' => 'Requisitos de contenido de la oferta técnica (Sobre B)',
                    'description' => 'Se valora claridad expositiva. Propuesta funcional: propuestas concretas y ejecutables. Propuesta tecnológica: arquitectura, componentes y mecanismos de integración. Plan ejecución: cronograma, fases, hitos y entregables. Gestión: metodología y herramientas; equipo con matriz RACI; plan de formación; seguimiento y control.',
                    'priority' => 'mandatory',
                    'criterion_type' => 'judgment',
                    'score_points' => '50',
                    'metadata' => [],
                ],
            ],
            'insights' => [],
        ],
    ])->preventStrayPrompts();

    (new ProcessDocumentAction)($document);

    $subcriteriaCount = \App\Models\ExtractedCriterion::query()
        ->where('document_id', $document->id)
        ->where('criterion_type', 'judgment')
        ->count();

    expect($subcriteriaCount)->toBeGreaterThan(4);

    assertDatabaseHas('extracted_criteria', [
        'document_id' => $document->id,
        'section_number' => '1.1',
        'section_title' => 'Propuesta de Evolución Funcional',
    ]);

    assertDatabaseHas('extracted_criteria', [
        'document_id' => $document->id,
        'section_number' => '2.4',
        'section_title' => 'Mecanismos de Seguimiento y Control',
    ]);
});

it('forces known legal compliance sections to automatic criteria', function (): void {
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
            'criteria' => [
                [
                    'section_number' => 'Cuadro de características M',
                    'section_title' => 'Condiciones especiales de ejecución (art. 202 LCSP)',
                    'description' => 'Cumplimiento laboral y pago a subcontratistas conforme normativa.',
                    'priority' => 'mandatory',
                    'criterion_type' => 'judgment',
                    'score_points' => null,
                    'metadata' => [],
                ],
            ],
            'insights' => [],
        ],
    ])->preventStrayPrompts();

    (new ProcessDocumentAction)($document);

    assertDatabaseHas('extracted_criteria', [
        'document_id' => $document->id,
        'section_title' => 'Condiciones especiales de ejecución (art. 202 LCSP)',
        'criterion_type' => 'automatic',
    ]);
});

it('does not semantically split already numbered judgment criteria', function (): void {
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
            'criteria' => [
                [
                    'section_number' => '1.3',
                    'section_title' => 'Plan de ejecución del contrato',
                    'description' => 'Cronograma, fases, hitos, entregables y coherencia metodológica.',
                    'priority' => 'mandatory',
                    'criterion_type' => 'judgment',
                    'score_points' => '4',
                    'metadata' => [],
                ],
            ],
            'insights' => [],
        ],
    ])->preventStrayPrompts();

    (new ProcessDocumentAction)($document);

    $judgmentCriteria = \App\Models\ExtractedCriterion::query()
        ->where('document_id', $document->id)
        ->where('criterion_type', 'judgment')
        ->get();

    expect($judgmentCriteria)->toHaveCount(1);
    expect($judgmentCriteria->first()?->section_number)->toBe('1.3');
    expect((float) $judgmentCriteria->first()?->score_points)->toBe(4.0);
});
