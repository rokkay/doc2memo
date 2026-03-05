<?php

declare(strict_types=1);

use App\Actions\Tenders\GenerateTechnicalMemoryAction;
use App\Models\Document;
use App\Models\ExtractedCriterion;
use App\Models\TechnicalMemoryMetricRun;
use App\Models\Tender;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;

uses(RefreshDatabase::class);

it('marks memory generated when there are no judgment criteria', function (): void {
    $tender = Tender::factory()->create();

    (new GenerateTechnicalMemoryAction)($tender);

    $memory = $tender->fresh()->technicalMemory;

    expect($memory)->not->toBeNull()
        ->and($memory?->status)->toBe('generated')
        ->and($memory?->generated_at)->not->toBeNull();
});

it('creates zero-weight sections when total points are zero', function (): void {
    Bus::fake();

    $tender = Tender::factory()->create();
    $pca = Document::factory()->create([
        'tender_id' => $tender->id,
        'document_type' => 'pca',
    ]);

    ExtractedCriterion::factory()->create([
        'tender_id' => $tender->id,
        'document_id' => $pca->id,
        'criterion_type' => 'judgment',
        'source' => 'analyzer',
        'group_key' => '',
        'section_number' => null,
        'section_title' => 'Sin numero',
        'score_points' => null,
        'description' => 'Descripcion no expandible',
    ]);

    (new GenerateTechnicalMemoryAction)($tender);

    $section = $tender->fresh()->technicalMemory?->sections()->first();

    expect($section)->not->toBeNull()
        ->and((float) ($section?->weight_percent ?? 1))->toBe(0.0)
        ->and((string) ($section?->group_key ?? ''))->not->toBe('');
});

it('runs batch finally callback and marks memory generated when no blocking sections remain', function (): void {
    Bus::fake();

    $tender = Tender::factory()->create();
    $pca = Document::factory()->create([
        'tender_id' => $tender->id,
        'document_type' => 'pca',
    ]);

    ExtractedCriterion::factory()->create([
        'tender_id' => $tender->id,
        'document_id' => $pca->id,
        'criterion_type' => 'judgment',
        'source' => 'analyzer',
        'group_key' => '1.1-criterio',
        'section_number' => '1.1',
        'section_title' => 'Criterio',
        'score_points' => 10,
        'description' => 'Descripcion base',
    ]);

    (new GenerateTechnicalMemoryAction)($tender);

    $memory = $tender->fresh()->technicalMemory;
    expect($memory)->not->toBeNull();

    $memory?->sections()->update([
        'status' => 'completed',
    ]);

    $runSummary = TechnicalMemoryMetricRun::query()->latest('id')->first();
    expect($runSummary)->not->toBeNull();

    $pendingBatch = Bus::dispatchedBatches()[0];
    $finally = $pendingBatch->finallyCallbacks()[0];
    $batch = Bus::findBatch((string) $runSummary?->batch_id);

    $finally($batch);

    expect($memory?->fresh()?->status)->toBe('generated')
        ->and($memory?->fresh()?->generated_at)->not->toBeNull();
});

it('runs batch finally callback safely when memory no longer exists', function (): void {
    Bus::fake();

    $tender = Tender::factory()->create();
    $pca = Document::factory()->create([
        'tender_id' => $tender->id,
        'document_type' => 'pca',
    ]);

    ExtractedCriterion::factory()->create([
        'tender_id' => $tender->id,
        'document_id' => $pca->id,
        'criterion_type' => 'judgment',
        'source' => 'analyzer',
        'group_key' => '1.1-criterio',
        'section_number' => '1.1',
        'section_title' => 'Criterio',
        'score_points' => 10,
        'description' => 'Descripcion base',
    ]);

    (new GenerateTechnicalMemoryAction)($tender);

    $runSummary = TechnicalMemoryMetricRun::query()->latest('id')->first();
    $memory = $tender->fresh()->technicalMemory;
    expect($runSummary)->not->toBeNull()
        ->and($memory)->not->toBeNull();

    $memory?->delete();

    $pendingBatch = Bus::dispatchedBatches()[0];
    $finally = $pendingBatch->finallyCallbacks()[0];
    $batch = Bus::findBatch((string) $runSummary?->batch_id);

    $finally($batch);

    expect(true)->toBeTrue();
});
