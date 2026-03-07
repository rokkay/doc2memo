<?php

declare(strict_types=1);

use App\Actions\Documents\ProcessDocumentAction;
use App\Ai\Agents\DocumentAnalyzer;
use App\Models\Document;
use App\Models\Tender;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;

use function Pest\Laravel\assertDatabaseHas;

uses(RefreshDatabase::class);

it('keeps process document orchestration behavior stable for ppt documents', function (): void {
    Storage::fake('local');

    $tender = Tender::factory()->pending()->create();
    $filePath = 'documents/'.$tender->id.'/ppt.md';

    Storage::disk('local')->put($filePath, '# PPT');

    $document = Document::factory()->create([
        'tender_id' => $tender->id,
        'document_type' => 'ppt',
        'file_path' => $filePath,
        'mime_type' => 'text/markdown',
        'status' => 'uploaded',
    ]);

    DocumentAnalyzer::fake([
        [
            'specifications' => [
                [
                    'section_number' => '2.1',
                    'section_title' => 'Alcance',
                    'technical_description' => 'Descripción técnica.',
                    'requirements' => 'Requisitos mínimos.',
                    'deliverables' => 'Entregables esperados.',
                ],
            ],
            'insights' => [
                [
                    'topic' => 'Operación',
                    'requirement_type' => 'technical',
                    'importance' => 'high',
                    'statement' => 'Disponibilidad 24/7.',
                    'evidence_excerpt' => 'Se exige continuidad.',
                ],
            ],
        ],
    ])->preventStrayPrompts();

    (new ProcessDocumentAction)($document);

    expect($document->fresh()->status)->toBe('analyzed')
        ->and($document->fresh()->insights_count)->toBe(1)
        ->and($tender->fresh()->status)->toBe('completed');

    assertDatabaseHas('extracted_specifications', [
        'document_id' => $document->id,
        'section_number' => '2.1',
        'section_title' => 'Alcance',
    ]);

    assertDatabaseHas('document_insights', [
        'document_id' => $document->id,
        'topic' => 'Operación',
        'importance' => 'high',
    ]);
});
