<?php

declare(strict_types=1);

use App\Actions\TechnicalMemories\UpsertMetricRunSummaryAction;
use App\Models\TechnicalMemory;
use App\Models\TechnicalMemoryMetricEvent;
use App\Models\TechnicalMemorySection;
use App\Models\Tender;
use App\Support\TechnicalMemoryMetrics;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('updates trigger sections total and batch id when existing run differs', function (): void {
    $memory = TechnicalMemory::factory()->create([
        'tender_id' => Tender::factory()->create()->id,
    ]);

    $run = $memory->metricRuns()->create([
        'run_id' => 'run-1',
        'trigger' => 'full_generation',
        'status' => 'running',
        'sections_total' => 1,
        'sections_completed' => 0,
        'sections_failed' => 0,
        'sections_retried' => 0,
        'duration_ms' => null,
        'batch_id' => null,
    ]);

    $updated = (new UpsertMetricRunSummaryAction)(
        memory: $memory,
        runId: 'run-1',
        trigger: 'section_regeneration',
        sectionsTotal: 2,
        batchId: 'batch-123',
    );

    expect($updated->id)->toBe($run->id)
        ->and($updated->fresh()->trigger)->toBe('section_regeneration')
        ->and($updated->fresh()->sections_total)->toBe(2)
        ->and($updated->fresh()->batch_id)->toBe('batch-123');
});

it('marks run as completed and computes counters from events', function (): void {
    $memory = TechnicalMemory::factory()->create([
        'tender_id' => Tender::factory()->create()->id,
    ]);

    $sectionA = TechnicalMemorySection::factory()->create([
        'technical_memory_id' => $memory->id,
    ]);
    $sectionB = TechnicalMemorySection::factory()->create([
        'technical_memory_id' => $memory->id,
    ]);

    $run = $memory->metricRuns()->create([
        'run_id' => 'run-2',
        'trigger' => 'full_generation',
        'status' => 'running',
        'sections_total' => 2,
        'sections_completed' => 0,
        'sections_failed' => 0,
        'sections_retried' => 0,
        'duration_ms' => null,
    ]);

    TechnicalMemoryMetricEvent::query()->forceCreate([
        'technical_memory_id' => $memory->id,
        'technical_memory_section_id' => $sectionA->id,
        'run_id' => 'run-2',
        'attempt' => 1,
        'event_type' => TechnicalMemoryMetrics::EVENT_COMPLETED,
        'status' => 'completed',
        'duration_ms' => 100,
        'quality_passed' => true,
        'quality_reasons' => [],
        'output_chars' => 100,
        'output_h3_count' => 1,
        'used_style_editor' => false,
        'metadata' => [],
        'created_at' => now()->subSeconds(3),
        'updated_at' => now()->subSeconds(3),
    ]);

    TechnicalMemoryMetricEvent::query()->forceCreate([
        'technical_memory_id' => $memory->id,
        'technical_memory_section_id' => $sectionB->id,
        'run_id' => 'run-2',
        'attempt' => 1,
        'event_type' => TechnicalMemoryMetrics::EVENT_REQUEUED,
        'status' => 'requeued',
        'duration_ms' => null,
        'quality_passed' => null,
        'quality_reasons' => null,
        'output_chars' => null,
        'output_h3_count' => null,
        'used_style_editor' => false,
        'metadata' => [],
        'created_at' => now()->subSeconds(2),
        'updated_at' => now()->subSeconds(2),
    ]);

    TechnicalMemoryMetricEvent::query()->forceCreate([
        'technical_memory_id' => $memory->id,
        'technical_memory_section_id' => $sectionB->id,
        'run_id' => 'run-2',
        'attempt' => 2,
        'event_type' => TechnicalMemoryMetrics::EVENT_FAILED,
        'status' => 'failed',
        'duration_ms' => 200,
        'quality_passed' => false,
        'quality_reasons' => ['low_quality'],
        'output_chars' => 80,
        'output_h3_count' => 1,
        'used_style_editor' => true,
        'metadata' => [],
        'created_at' => now()->subSecond(),
        'updated_at' => now()->subSecond(),
    ]);

    $updated = (new UpsertMetricRunSummaryAction)(
        memory: $memory,
        runId: 'run-2',
    );

    expect($updated->id)->toBe($run->id)
        ->and($updated->fresh()->status)->toBe('completed')
        ->and($updated->fresh()->sections_completed)->toBe(1)
        ->and($updated->fresh()->sections_failed)->toBe(1)
        ->and($updated->fresh()->sections_retried)->toBe(1)
        ->and($updated->fresh()->duration_ms)->not->toBeNull();
});
