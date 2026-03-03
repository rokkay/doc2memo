<?php

declare(strict_types=1);

use App\Actions\Documents\RefreshTenderStatusAction;
use App\Models\Document;
use App\Models\Tender;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

it('sets tender to failed when any document failed', function (): void {
    $tender = Tender::factory()->analyzing()->create();

    $failedDocument = Document::factory()->create([
        'tender_id' => $tender->id,
        'status' => 'failed',
    ]);

    Document::factory()->create([
        'tender_id' => $tender->id,
        'status' => 'analyzed',
    ]);

    (new RefreshTenderStatusAction)($failedDocument);

    expect($tender->fresh()->status)->toBe('failed');
});

it('sets tender to analyzing when at least one document is not analyzed', function (): void {
    $tender = Tender::factory()->pending()->create();

    $processingDocument = Document::factory()->create([
        'tender_id' => $tender->id,
        'status' => 'processing',
    ]);

    Document::factory()->create([
        'tender_id' => $tender->id,
        'status' => 'analyzed',
    ]);

    (new RefreshTenderStatusAction)($processingDocument);

    expect($tender->fresh()->status)->toBe('analyzing');
});

it('sets tender to completed when all documents are analyzed', function (): void {
    $tender = Tender::factory()->analyzing()->create();

    $document = Document::factory()->create([
        'tender_id' => $tender->id,
        'status' => 'analyzed',
    ]);

    Document::factory()->create([
        'tender_id' => $tender->id,
        'status' => 'analyzed',
    ]);

    (new RefreshTenderStatusAction)($document);

    expect($tender->fresh()->status)->toBe('completed');
});
