<?php

declare(strict_types=1);

use App\Models\TechnicalMemory;
use App\Models\TechnicalMemorySection;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;

use function Pest\Laravel\artisan;

uses(RefreshDatabase::class);

it('purges technical memory metrics older than configured retention days', function (): void {
    Carbon::setTestNow('2026-02-14 12:00:00');

    $memory = TechnicalMemory::factory()->create();
    $section = TechnicalMemorySection::factory()->create([
        'technical_memory_id' => $memory->id,
    ]);

    $staleRun = $memory->metricRuns()->create([
        'run_id' => 'run-stale',
        'trigger' => 'full_generation',
        'status' => 'completed',
        'sections_total' => 1,
        'sections_completed' => 1,
        'sections_failed' => 0,
        'sections_retried' => 0,
        'duration_ms' => 3000,
    ]);
    $staleRun->forceFill([
        'created_at' => now()->subDays(120),
        'updated_at' => now()->subDays(120),
    ])->saveQuietly();

    $freshRun = $memory->metricRuns()->create([
        'run_id' => 'run-fresh',
        'trigger' => 'full_generation',
        'status' => 'completed',
        'sections_total' => 1,
        'sections_completed' => 1,
        'sections_failed' => 0,
        'sections_retried' => 0,
        'duration_ms' => 2000,
    ]);
    $freshRun->forceFill([
        'created_at' => now()->subDays(10),
        'updated_at' => now()->subDays(10),
    ])->saveQuietly();

    $staleEvent = $memory->metricEvents()->create([
        'technical_memory_section_id' => $section->id,
        'run_id' => 'run-stale',
        'attempt' => 1,
        'event_type' => 'completed',
        'status' => 'completed',
        'duration_ms' => 1800,
    ]);
    $staleEvent->forceFill([
        'created_at' => now()->subDays(120),
        'updated_at' => now()->subDays(120),
    ])->saveQuietly();

    $freshEvent = $memory->metricEvents()->create([
        'technical_memory_section_id' => $section->id,
        'run_id' => 'run-fresh',
        'attempt' => 1,
        'event_type' => 'completed',
        'status' => 'completed',
        'duration_ms' => 1700,
    ]);
    $freshEvent->forceFill([
        'created_at' => now()->subDays(10),
        'updated_at' => now()->subDays(10),
    ])->saveQuietly();

    artisan('technical-memory:purge-metrics')
        ->expectsOutput('Purged technical memory metrics older than 90 days. Events: 1, Runs: 1.')
        ->assertSuccessful();

    expect($memory->metricRuns()->whereKey($staleRun->id)->exists())->toBeFalse()
        ->and($memory->metricRuns()->whereKey($freshRun->id)->exists())->toBeTrue()
        ->and($memory->metricEvents()->whereKey($staleEvent->id)->exists())->toBeFalse()
        ->and($memory->metricEvents()->whereKey($freshEvent->id)->exists())->toBeTrue();

    Carbon::setTestNow();
});
