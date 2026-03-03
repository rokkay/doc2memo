<?php

declare(strict_types=1);

use App\Actions\Documents\PersistInsightsAction;
use App\Models\Document;
use App\Models\Tender;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

use function Pest\Laravel\assertDatabaseHas;

uses(TestCase::class, RefreshDatabase::class);

it('persists insights and returns inserted row count', function (): void {
    $tender = Tender::factory()->create();
    $document = Document::factory()->create(['tender_id' => $tender->id]);

    $insights = [
        [
            'section_reference' => '2.1',
            'topic' => 'Arquitectura',
            'requirement_type' => 'technical',
            'importance' => 'high',
            'statement' => 'Se requiere alta disponibilidad.',
            'evidence_excerpt' => 'El servicio debe estar disponible 24/7.',
            'metadata' => ['source' => 'analyzer'],
        ],
        [
            'statement' => 'Se valora experiencia previa.',
        ],
    ];

    $count = (new PersistInsightsAction)($document, $insights);

    expect($count)->toBe(2);

    assertDatabaseHas('document_insights', [
        'document_id' => $document->id,
        'topic' => 'Arquitectura',
        'importance' => 'high',
    ]);

    assertDatabaseHas('document_insights', [
        'document_id' => $document->id,
        'topic' => 'General',
        'importance' => 'medium',
    ]);
});
