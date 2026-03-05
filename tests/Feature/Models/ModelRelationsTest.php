<?php

declare(strict_types=1);

use App\Models\AiCostEntry;
use App\Models\Document;
use App\Models\DocumentInsight;
use App\Models\ExtractedCriterion;
use App\Models\ExtractedSpecification;
use App\Models\TechnicalMemory;
use App\Models\TechnicalMemoryGenerationMetric;
use App\Models\TechnicalMemoryMetricEvent;
use App\Models\TechnicalMemoryMetricRun;
use App\Models\TechnicalMemorySection;
use App\Models\Tender;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('exposes expected document relations', function (): void {
    $document = new Document;

    expect($document->tender())->toBeInstanceOf(BelongsTo::class)
        ->and($document->extractedCriteria())->toBeInstanceOf(HasMany::class)
        ->and($document->extractedSpecifications())->toBeInstanceOf(HasMany::class)
        ->and($document->insights())->toBeInstanceOf(HasMany::class)
        ->and($document->aiCostEntries())->toBeInstanceOf(HasMany::class);
});

it('exposes expected technical memory relations', function (): void {
    $memory = new TechnicalMemory;

    expect($memory->tender())->toBeInstanceOf(BelongsTo::class)
        ->and($memory->sections())->toBeInstanceOf(HasMany::class)
        ->and($memory->metricRuns())->toBeInstanceOf(HasMany::class)
        ->and($memory->metricEvents())->toBeInstanceOf(HasMany::class)
        ->and($memory->generationMetrics())->toBeInstanceOf(HasMany::class)
        ->and($memory->aiCostEntries())->toBeInstanceOf(HasMany::class);
});

it('exposes expected metric and insight relations', function (): void {
    expect((new TechnicalMemoryGenerationMetric)->technicalMemory())->toBeInstanceOf(BelongsTo::class)
        ->and((new TechnicalMemoryGenerationMetric)->technicalMemorySection())->toBeInstanceOf(BelongsTo::class)
        ->and((new TechnicalMemoryGenerationMetric)->aiCostEntries())->toBeInstanceOf(HasMany::class)
        ->and((new TechnicalMemoryMetricEvent)->technicalMemory())->toBeInstanceOf(BelongsTo::class)
        ->and((new TechnicalMemoryMetricEvent)->technicalMemorySection())->toBeInstanceOf(BelongsTo::class)
        ->and((new TechnicalMemoryMetricRun)->technicalMemory())->toBeInstanceOf(BelongsTo::class)
        ->and((new DocumentInsight)->tender())->toBeInstanceOf(BelongsTo::class)
        ->and((new DocumentInsight)->document())->toBeInstanceOf(BelongsTo::class)
        ->and((new ExtractedSpecification)->tender())->toBeInstanceOf(BelongsTo::class)
        ->and((new ExtractedSpecification)->document())->toBeInstanceOf(BelongsTo::class)
        ->and((new AiCostEntry)->tender())->toBeInstanceOf(BelongsTo::class)
        ->and((new AiCostEntry)->document())->toBeInstanceOf(BelongsTo::class)
        ->and((new AiCostEntry)->technicalMemory())->toBeInstanceOf(BelongsTo::class)
        ->and((new AiCostEntry)->technicalMemorySection())->toBeInstanceOf(BelongsTo::class)
        ->and((new AiCostEntry)->technicalMemoryGenerationMetric())->toBeInstanceOf(BelongsTo::class)
        ->and((new TechnicalMemorySection)->technicalMemory())->toBeInstanceOf(BelongsTo::class)
        ->and((new TechnicalMemorySection)->metricEvents())->toBeInstanceOf(HasMany::class)
        ->and((new TechnicalMemorySection)->generationMetrics())->toBeInstanceOf(HasMany::class);
});

it('applies extracted criterion scopes for judgment and automatic types', function (): void {
    $tender = Tender::factory()->create();
    $document = Document::factory()->create(['tender_id' => $tender->id]);

    ExtractedCriterion::factory()->create([
        'tender_id' => $tender->id,
        'document_id' => $document->id,
        'criterion_type' => 'judgment',
    ]);

    ExtractedCriterion::factory()->create([
        'tender_id' => $tender->id,
        'document_id' => $document->id,
        'criterion_type' => 'automatic',
    ]);

    expect(ExtractedCriterion::query()->judgment()->count())->toBe(1)
        ->and(ExtractedCriterion::query()->automatic()->count())->toBe(1);

    expect((new ExtractedCriterion)->tender())->toBeInstanceOf(BelongsTo::class)
        ->and((new ExtractedCriterion)->document())->toBeInstanceOf(BelongsTo::class);
});
