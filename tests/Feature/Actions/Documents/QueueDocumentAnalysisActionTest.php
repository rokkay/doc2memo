<?php

declare(strict_types=1);

use App\Actions\Documents\QueueDocumentAnalysisAction;
use App\Ai\Agents\DocumentAnalyzer;
use App\Models\Document;
use App\Models\Tender;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Laravel\Ai\QueuedAgentPrompt;

uses(RefreshDatabase::class);

it('queues document analyzer prompts and marks document as processing', function (): void {
    Storage::fake('local');

    $tender = Tender::factory()->pending()->create();
    $filePath = 'documents/'.$tender->id.'/pca.md';
    Storage::disk('local')->put($filePath, "# PCA\n\nTexto de prueba para cola");

    $document = Document::factory()->create([
        'tender_id' => $tender->id,
        'document_type' => 'pca',
        'file_path' => $filePath,
        'mime_type' => 'text/markdown',
        'status' => 'uploaded',
    ]);

    DocumentAnalyzer::fake()->preventStrayPrompts();

    resolve(QueueDocumentAnalysisAction::class)($document);

    $processedDocument = $document->fresh();

    expect($processedDocument?->status)->toBe('processing')
        ->and($processedDocument?->processing_error)->toBeNull();

    DocumentAnalyzer::assertQueued(function (QueuedAgentPrompt $prompt): bool {
        return $prompt->agent instanceof DocumentAnalyzer
            && $prompt->prompt !== ''
            && str_contains($prompt->prompt, 'PCA');
    });
});
