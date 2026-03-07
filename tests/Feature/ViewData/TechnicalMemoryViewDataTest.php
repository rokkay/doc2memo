<?php

declare(strict_types=1);

use App\Models\ExtractedCriterion;
use App\Models\TechnicalMemory;
use App\Models\TechnicalMemorySection;
use App\Models\Tender;
use App\ViewData\TechnicalMemoryViewData;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('returns zero progress when memory has no sections', function (): void {
    $tender = Tender::factory()->create();

    TechnicalMemory::factory()->create([
        'tender_id' => $tender->id,
    ]);

    $viewData = TechnicalMemoryViewData::fromTender($tender->fresh()->load('technicalMemory.sections', 'extractedCriteria'));

    expect($viewData->hasMemory)->toBeTrue()
        ->and($viewData->totalCount)->toBe(0)
        ->and($viewData->progressPercent)->toBe(0);
});

it('uses puntos nd when evidence criterion has null score points', function (): void {
    $tender = Tender::factory()->create();
    $memory = TechnicalMemory::factory()->create([
        'tender_id' => $tender->id,
    ]);

    TechnicalMemorySection::factory()->create([
        'technical_memory_id' => $memory->id,
        'group_key' => 'group-a',
        'section_title' => 'Metodología',
        'status' => 'completed',
        'content' => 'Contenido',
        'sort_order' => 1,
    ]);

    ExtractedCriterion::factory()->create([
        'tender_id' => $tender->id,
        'group_key' => 'group-a',
        'criterion_type' => 'judgment',
        'score_points' => null,
        'description' => 'Evidencia sin puntos',
    ]);

    $viewData = TechnicalMemoryViewData::fromTender($tender->fresh()->load('technicalMemory.sections', 'extractedCriteria'));

    expect($viewData->sections[0]['evidence'][0]['label'])->toContain('Puntos N/D');
});
