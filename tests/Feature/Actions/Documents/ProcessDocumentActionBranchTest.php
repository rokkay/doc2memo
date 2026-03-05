<?php

declare(strict_types=1);

use App\Actions\Documents\AnalyzeDocumentWithMetricsAction;
use App\Actions\Documents\ProcessDocumentAction;
use App\Models\Document;
use App\Models\Tender;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

it('marks tender failed when another document is already failed', function (): void {
    Storage::fake('local');

    $tender = Tender::factory()->create(['status' => 'analyzing']);

    Document::factory()->create([
        'tender_id' => $tender->id,
        'status' => 'failed',
    ]);

    $ppt = Document::factory()->create([
        'tender_id' => $tender->id,
        'document_type' => 'ppt',
        'status' => 'uploaded',
        'mime_type' => 'text/plain',
        'file_path' => 'documents/'.$tender->id.'/ppt.txt',
    ]);

    Storage::disk('local')->put($ppt->file_path, 'contenido ppt');

    app()->instance(AnalyzeDocumentWithMetricsAction::class, new class
    {
        public function __invoke(Document $document, string $text): array
        {
            return [
                'analysis' => [
                    'specifications' => [[
                        'section_number' => '1',
                        'section_title' => 'Arquitectura',
                        'technical_description' => 'Descripcion',
                        'requirements' => 'Req',
                        'deliverables' => 'Del',
                        'metadata' => ['k' => 'v'],
                    ]],
                    'insights' => [],
                ],
                'costSummary' => [
                    'breakdown' => [
                        'document_analyzer' => [
                            'model_name' => 'gpt-5.2',
                            'status' => 'completed',
                            'input_chars' => 10,
                            'output_chars' => 10,
                            'estimated_input_units' => 0.001,
                            'estimated_output_units' => 0.001,
                            'estimated_cost_usd' => 0.001,
                        ],
                        'dedicated_judgment_extractor' => [
                            'model_name' => 'gpt-5-mini',
                            'status' => 'skipped',
                            'input_chars' => 0,
                            'output_chars' => 0,
                            'estimated_input_units' => 0.0,
                            'estimated_output_units' => 0.0,
                            'estimated_cost_usd' => 0.0,
                        ],
                    ],
                ],
                'dedicatedCriteria' => [],
            ];
        }
    });

    (new ProcessDocumentAction)($ppt->fresh());

    expect($tender->fresh()->status)->toBe('failed');
});

it('marks tender analyzing when at least one document remains not analyzed', function (): void {
    Storage::fake('local');

    $tender = Tender::factory()->create(['status' => 'pending']);

    Document::factory()->create([
        'tender_id' => $tender->id,
        'status' => 'processing',
    ]);

    $ppt = Document::factory()->create([
        'tender_id' => $tender->id,
        'document_type' => 'ppt',
        'status' => 'uploaded',
        'mime_type' => 'text/plain',
        'file_path' => 'documents/'.$tender->id.'/ppt-2.txt',
    ]);

    Storage::disk('local')->put($ppt->file_path, 'contenido ppt');

    app()->instance(AnalyzeDocumentWithMetricsAction::class, new class
    {
        public function __invoke(Document $document, string $text): array
        {
            return [
                'analysis' => ['specifications' => [], 'insights' => []],
                'costSummary' => ['breakdown' => []],
                'dedicatedCriteria' => [],
            ];
        }
    });

    (new ProcessDocumentAction)($ppt->fresh());

    expect($tender->fresh()->status)->toBe('analyzing');
});

it('extracts text through pdf parser branch and covers helper branches via reflection', function (): void {
    Storage::fake('local');

    $tender = Tender::factory()->create();
    $document = Document::factory()->create([
        'tender_id' => $tender->id,
        'document_type' => 'ppt',
        'file_path' => 'documents/'.$tender->id.'/sample.pdf',
    ]);

    Storage::disk('local')->put($document->file_path, 'fake-pdf-content');

    $fakePdf = \Mockery::mock();
    $fakePdf->shouldReceive('getText')->once()->andReturn('pdf text');

    $parser = \Mockery::mock('overload:Smalot\\PdfParser\\Parser');
    $parser->shouldReceive('parseFile')->once()->andReturn($fakePdf);

    $action = new ProcessDocumentAction;

    $extractText = new ReflectionMethod(ProcessDocumentAction::class, 'extractText');

    expect($extractText->invoke($action, $document))->toBe('pdf text');

    $resolveSourceReference = new ReflectionMethod(ProcessDocumentAction::class, 'resolveSourceReference');

    expect($resolveSourceReference->invoke($action, '1.2', 'Titulo', ['source_reference' => 'Ref']))->toBe('Ref')
        ->and($resolveSourceReference->invoke($action, '2.1', 'Seccion', []))->toBe('2.1 Seccion')
        ->and($resolveSourceReference->invoke($action, '2.1', '', []))->toBe('2.1')
        ->and($resolveSourceReference->invoke($action, null, '', []))->toBeNull();

    $extractScorePoints = new ReflectionMethod(ProcessDocumentAction::class, 'extractScorePoints');

    expect($extractScorePoints->invoke($action, null, 'hasta 12,5 puntos', []))->toBe(12.5)
        ->and($extractScorePoints->invoke($action, null, 'sin puntos', ['max_points' => '8']))->toBe(8.0)
        ->and($extractScorePoints->invoke($action, null, 'sin valor', []))->toBeNull();

    $normalizeCriterionType = new ReflectionMethod(ProcessDocumentAction::class, 'normalizeCriterionType');

    expect($normalizeCriterionType->invoke($action, 'unknown', 'Juicio de valor', 'texto'))->toBe('judgment')
        ->and($normalizeCriterionType->invoke($action, 'unknown', 'Precio por hora', 'texto'))->toBe('automatic')
        ->and($normalizeCriterionType->invoke($action, 'automatic', 'titulo', 'detalle'))->toBe('automatic')
        ->and($normalizeCriterionType->invoke($action, 'unknown', 'titulo', 'detalle'))->toBe('judgment');

    $parseNumericValue = new ReflectionMethod(ProcessDocumentAction::class, 'parseNumericValue');

    expect($parseNumericValue->invoke($action, ''))->toBeNull()
        ->and($parseNumericValue->invoke($action, 'abc'))->toBeNull();
});
