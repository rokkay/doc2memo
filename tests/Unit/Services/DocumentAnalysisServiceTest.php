<?php

declare(strict_types=1);

use App\Actions\Tenders\AnalyzeTenderDocumentsAction;
use App\Jobs\ProcessDocument;
use App\Models\Document;
use App\Models\Tender;
use App\Services\DocumentAnalysisService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

it('delegates tender analysis to action', function (): void {
    Bus::fake();

    $tender = Tender::factory()->create();
    Document::factory()->create([
        'tender_id' => $tender->id,
        'status' => 'uploaded',
    ]);

    $service = new DocumentAnalysisService(resolve(AnalyzeTenderDocumentsAction::class));
    $service->analyzeTender($tender);

    expect($tender->fresh()->status)->toBe('analyzing');
    Bus::assertDispatched(ProcessDocument::class);
});

it('returns early when document does not belong to tender', function (): void {
    Bus::fake();

    $tender = Tender::factory()->create();
    $otherTender = Tender::factory()->create();
    $document = Document::factory()->create([
        'tender_id' => $otherTender->id,
        'status' => 'uploaded',
    ]);

    $service = new DocumentAnalysisService(resolve(AnalyzeTenderDocumentsAction::class));
    $service->analyzeDocument($tender, $document);

    expect($document->fresh()->status)->toBe('uploaded');
    Bus::assertNotDispatched(ProcessDocument::class);
});

it('returns early when document is not in analyzable status', function (): void {
    Bus::fake();

    $tender = Tender::factory()->create();
    $document = Document::factory()->create([
        'tender_id' => $tender->id,
        'status' => 'processing',
    ]);

    $service = new DocumentAnalysisService(resolve(AnalyzeTenderDocumentsAction::class));
    $service->analyzeDocument($tender, $document);

    expect($document->fresh()->status)->toBe('processing');
    Bus::assertNotDispatched(ProcessDocument::class);
});

it('queues analysis and updates tender status for analyzable documents', function (): void {
    Bus::fake();

    $tender = Tender::factory()->create(['status' => 'pending']);
    $document = Document::factory()->create([
        'tender_id' => $tender->id,
        'status' => 'failed',
        'processing_error' => 'temporary issue',
    ]);

    $service = new DocumentAnalysisService(resolve(AnalyzeTenderDocumentsAction::class));
    $service->analyzeDocument($tender, $document);

    expect($document->fresh()->status)->toBe('processing')
        ->and($document->fresh()->processing_error)->toBeNull()
        ->and($tender->fresh()->status)->toBe('analyzing');

    Bus::assertDispatched(ProcessDocument::class, fn (ProcessDocument $job): bool => $job->document->is($document));
});
