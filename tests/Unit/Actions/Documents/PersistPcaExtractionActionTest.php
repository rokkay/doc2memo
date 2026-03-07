<?php

declare(strict_types=1);

use App\Actions\Documents\PersistPcaExtractionAction;
use App\Models\Document;
use App\Models\Tender;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

use function Pest\Laravel\assertDatabaseHas;

uses(TestCase::class, RefreshDatabase::class);

it('forces legal compliance sections to automatic criterion type', function (): void {
    $tender = Tender::factory()->pending()->create();
    $document = Document::factory()->create([
        'tender_id' => $tender->id,
        'document_type' => 'pca',
    ]);

    $analysis = [
        'criteria' => [
            [
                'section_number' => 'M',
                'section_title' => 'Condiciones especiales de ejecución (art. 202 LCSP)',
                'description' => 'Cumplimiento laboral y subcontratistas.',
                'priority' => 'mandatory',
                'criterion_type' => 'judgment',
                'score_points' => null,
                'metadata' => [],
            ],
        ],
    ];

    (new PersistPcaExtractionAction)($document, $analysis, []);

    assertDatabaseHas('extracted_criteria', [
        'document_id' => $document->id,
        'section_title' => 'Condiciones especiales de ejecución (art. 202 LCSP)',
        'criterion_type' => 'automatic',
    ]);
});

it('extracts score points from description when primary score is missing', function (): void {
    $tender = Tender::factory()->pending()->create();
    $document = Document::factory()->create([
        'tender_id' => $tender->id,
        'document_type' => 'pca',
    ]);

    $analysis = [
        'criteria' => [
            [
                'section_number' => '3.1',
                'section_title' => 'Gobierno del servicio',
                'description' => 'Hasta 18 puntos por modelo de seguimiento operativo.',
                'priority' => 'mandatory',
                'criterion_type' => 'judgment',
                'score_points' => null,
                'metadata' => [],
            ],
        ],
    ];

    (new PersistPcaExtractionAction)($document, $analysis, []);

    assertDatabaseHas('extracted_criteria', [
        'document_id' => $document->id,
        'section_title' => 'Gobierno del servicio',
        'score_points' => 18.0,
    ]);
});
