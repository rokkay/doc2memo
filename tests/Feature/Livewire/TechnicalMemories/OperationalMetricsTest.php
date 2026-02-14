<?php

declare(strict_types=1);

use App\Livewire\TechnicalMemories\OperationalMetrics;
use App\Models\TechnicalMemory;
use App\Models\TechnicalMemoryGenerationMetric;
use App\Models\TechnicalMemorySection;
use App\Models\Tender;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses()->group('livewire');
uses(RefreshDatabase::class);

it('renders kpi cards and metrics tables', function (): void {
    $tender = Tender::factory()->create();
    $memory = TechnicalMemory::factory()->create([
        'tender_id' => $tender->id,
        'title' => 'Memory Dashboard A',
    ]);
    $section = TechnicalMemorySection::factory()->create([
        'technical_memory_id' => $memory->id,
        'section_title' => 'Section Problematic',
    ]);

    TechnicalMemoryGenerationMetric::query()->forceCreate([
        'technical_memory_id' => $memory->id,
        'technical_memory_section_id' => $section->id,
        'run_id' => 'dashboard-run-1',
        'attempt' => 1,
        'status' => 'failed',
        'quality_passed' => false,
        'quality_reasons' => ['low_quality'],
        'duration_ms' => 1600,
        'output_chars' => 600,
        'model_name' => 'gpt-5-mini',
        'estimated_input_units' => 0.001,
        'estimated_output_units' => 0.001,
        'estimated_cost_usd' => 0.2,
        'created_at' => CarbonImmutable::parse('2026-02-12 10:00:00'),
        'updated_at' => CarbonImmutable::parse('2026-02-12 10:00:00'),
    ]);

    TechnicalMemoryGenerationMetric::query()->forceCreate([
        'technical_memory_id' => $memory->id,
        'technical_memory_section_id' => $section->id,
        'run_id' => 'dashboard-run-1',
        'attempt' => 2,
        'status' => 'completed',
        'quality_passed' => true,
        'quality_reasons' => [],
        'duration_ms' => 2100,
        'output_chars' => 1900,
        'model_name' => 'gpt-5-mini',
        'estimated_input_units' => 0.001,
        'estimated_output_units' => 0.001,
        'estimated_cost_usd' => 0.25,
        'created_at' => CarbonImmutable::parse('2026-02-12 10:05:00'),
        'updated_at' => CarbonImmutable::parse('2026-02-12 10:05:00'),
    ]);

    Livewire::test(OperationalMetrics::class)
        ->assertSee('First pass')
        ->assertSee('Retry')
        ->assertSee('Failure')
        ->assertSee('Duracion media')
        ->assertSee('Coste estimado')
        ->assertSee('Memory Dashboard A')
        ->assertSee('Section Problematic');
});

it('updates kpis when date filters change', function (): void {
    $tender = Tender::factory()->create();
    $memory = TechnicalMemory::factory()->create([
        'tender_id' => $tender->id,
    ]);
    $section = TechnicalMemorySection::factory()->create([
        'technical_memory_id' => $memory->id,
    ]);

    TechnicalMemoryGenerationMetric::query()->forceCreate([
        'technical_memory_id' => $memory->id,
        'technical_memory_section_id' => $section->id,
        'run_id' => 'dashboard-filter-1',
        'attempt' => 1,
        'status' => 'completed',
        'quality_passed' => true,
        'quality_reasons' => [],
        'duration_ms' => 1100,
        'output_chars' => 1850,
        'model_name' => 'gpt-5-mini',
        'estimated_input_units' => 0.001,
        'estimated_output_units' => 0.001,
        'estimated_cost_usd' => 0.11,
        'created_at' => CarbonImmutable::parse('2026-02-10 10:00:00'),
        'updated_at' => CarbonImmutable::parse('2026-02-10 10:00:00'),
    ]);

    TechnicalMemoryGenerationMetric::query()->forceCreate([
        'technical_memory_id' => $memory->id,
        'technical_memory_section_id' => $section->id,
        'run_id' => 'dashboard-filter-2',
        'attempt' => 1,
        'status' => 'completed',
        'quality_passed' => true,
        'quality_reasons' => [],
        'duration_ms' => 900,
        'output_chars' => 1700,
        'model_name' => 'gpt-5-mini',
        'estimated_input_units' => 0.001,
        'estimated_output_units' => 0.001,
        'estimated_cost_usd' => 0.22,
        'created_at' => CarbonImmutable::parse('2026-02-11 10:00:00'),
        'updated_at' => CarbonImmutable::parse('2026-02-11 10:00:00'),
    ]);

    Livewire::test(OperationalMetrics::class)
        ->assertSet('metrics.global.attempts', 2)
        ->set('from_date', '2026-02-11')
        ->set('to_date', '2026-02-11')
        ->assertSet('metrics.global.attempts', 1)
        ->assertSet('metrics.global.estimated_cost_usd', 0.22);
});
