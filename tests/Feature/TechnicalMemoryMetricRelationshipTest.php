<?php

declare(strict_types=1);

use App\Models\TechnicalMemory;
use App\Models\TechnicalMemorySection;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('loads metric runs and metric events relationships from memory and section', function (): void {
    $memory = TechnicalMemory::factory()->create();
    $section = TechnicalMemorySection::factory()->create([
        'technical_memory_id' => $memory->id,
    ]);

    $memory->metricRuns()->create([
        'run_id' => 'run-1',
        'trigger' => 'full_generation',
        'status' => 'running',
        'sections_total' => 1,
        'sections_completed' => 0,
        'sections_failed' => 0,
        'sections_retried' => 0,
    ]);

    $memory->metricEvents()->create([
        'technical_memory_section_id' => $section->id,
        'run_id' => 'run-1',
        'attempt' => 1,
        'event_type' => 'started',
    ]);

    $loadedMemory = TechnicalMemory::query()
        ->with(['metricRuns', 'metricEvents'])
        ->findOrFail($memory->id);

    $loadedSection = TechnicalMemorySection::query()
        ->with('metricEvents')
        ->findOrFail($section->id);

    expect($loadedMemory->metricRuns)->toHaveCount(1)
        ->and($loadedMemory->metricEvents)->toHaveCount(1)
        ->and($loadedSection->metricEvents)->toHaveCount(1);
});
