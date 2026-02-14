<?php

declare(strict_types=1);

use App\Actions\TechnicalMemories\GetOperationalMetricsAction;
use App\Models\Document;
use App\Models\TechnicalMemory;
use App\Models\TechnicalMemoryGenerationMetric;
use App\Models\TechnicalMemorySection;
use App\Models\Tender;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('calculates global kpis durations and per memory rollups', function (): void {
    $tender = Tender::factory()->create();

    $memoryA = TechnicalMemory::factory()->create([
        'tender_id' => $tender->id,
        'title' => 'Memory A',
    ]);

    $memoryB = TechnicalMemory::factory()->create([
        'tender_id' => $tender->id,
        'title' => 'Memory B',
    ]);

    $sectionA = TechnicalMemorySection::factory()->create([
        'technical_memory_id' => $memoryA->id,
        'section_title' => 'Section A',
    ]);

    $sectionB = TechnicalMemorySection::factory()->create([
        'technical_memory_id' => $memoryA->id,
        'section_title' => 'Section B',
    ]);

    $sectionC = TechnicalMemorySection::factory()->create([
        'technical_memory_id' => $memoryB->id,
        'section_title' => 'Section C',
    ]);

    TechnicalMemoryGenerationMetric::query()->forceCreate([
        'technical_memory_id' => $memoryA->id,
        'technical_memory_section_id' => $sectionA->id,
        'run_id' => 'run-a',
        'attempt' => 1,
        'status' => 'completed',
        'quality_passed' => true,
        'quality_reasons' => [],
        'duration_ms' => 1000,
        'output_chars' => 1800,
        'model_name' => 'gpt-5-mini',
        'estimated_input_units' => 0.001,
        'estimated_output_units' => 0.001,
        'estimated_cost_usd' => 0.1,
        'agent_cost_breakdown' => [
            'dynamic_section' => ['estimated_cost_usd' => 0.07],
            'style_editor' => ['estimated_cost_usd' => 0.03],
        ],
        'created_at' => CarbonImmutable::parse('2026-02-10 10:00:00'),
        'updated_at' => CarbonImmutable::parse('2026-02-10 10:00:00'),
    ]);

    TechnicalMemoryGenerationMetric::query()->forceCreate([
        'technical_memory_id' => $memoryA->id,
        'technical_memory_section_id' => $sectionB->id,
        'run_id' => 'run-a',
        'attempt' => 1,
        'status' => 'failed',
        'quality_passed' => false,
        'quality_reasons' => ['too_short'],
        'duration_ms' => 2000,
        'output_chars' => 500,
        'model_name' => 'gpt-5-mini',
        'estimated_input_units' => 0.001,
        'estimated_output_units' => 0.001,
        'estimated_cost_usd' => 0.2,
        'agent_cost_breakdown' => [
            'dynamic_section' => ['estimated_cost_usd' => 0.14],
            'style_editor' => ['estimated_cost_usd' => 0.06],
        ],
        'created_at' => CarbonImmutable::parse('2026-02-10 10:05:00'),
        'updated_at' => CarbonImmutable::parse('2026-02-10 10:05:00'),
    ]);

    TechnicalMemoryGenerationMetric::query()->forceCreate([
        'technical_memory_id' => $memoryA->id,
        'technical_memory_section_id' => $sectionB->id,
        'run_id' => 'run-a',
        'attempt' => 2,
        'status' => 'completed',
        'quality_passed' => true,
        'quality_reasons' => [],
        'duration_ms' => 3000,
        'output_chars' => 1900,
        'model_name' => 'gpt-5-mini',
        'estimated_input_units' => 0.001,
        'estimated_output_units' => 0.001,
        'estimated_cost_usd' => 0.15,
        'agent_cost_breakdown' => [
            'dynamic_section' => ['estimated_cost_usd' => 0.1],
            'style_editor' => ['estimated_cost_usd' => 0.05],
        ],
        'created_at' => CarbonImmutable::parse('2026-02-11 11:00:00'),
        'updated_at' => CarbonImmutable::parse('2026-02-11 11:00:00'),
    ]);

    TechnicalMemoryGenerationMetric::query()->forceCreate([
        'technical_memory_id' => $memoryB->id,
        'technical_memory_section_id' => $sectionC->id,
        'run_id' => 'run-b',
        'attempt' => 1,
        'status' => 'completed',
        'quality_passed' => true,
        'quality_reasons' => [],
        'duration_ms' => 500,
        'output_chars' => 2000,
        'model_name' => 'gpt-5.2',
        'estimated_input_units' => 0.001,
        'estimated_output_units' => 0.001,
        'estimated_cost_usd' => 0.05,
        'agent_cost_breakdown' => [
            'dynamic_section' => ['estimated_cost_usd' => 0.04],
            'style_editor' => ['estimated_cost_usd' => 0.01],
        ],
        'created_at' => CarbonImmutable::parse('2026-02-11 12:00:00'),
        'updated_at' => CarbonImmutable::parse('2026-02-11 12:00:00'),
    ]);

    Document::factory()->create([
        'tender_id' => $tender->id,
        'document_type' => 'pca',
        'status' => 'analyzed',
        'estimated_analysis_input_units' => 0.004,
        'estimated_analysis_output_units' => 0.001,
        'estimated_analysis_cost_usd' => 0.09,
        'analysis_cost_breakdown' => [
            'document_analyzer' => ['estimated_cost_usd' => 0.06],
            'dedicated_judgment_extractor' => ['estimated_cost_usd' => 0.03],
        ],
        'analyzed_at' => CarbonImmutable::parse('2026-02-11 13:00:00'),
    ]);

    $result = (new GetOperationalMetricsAction)(
        from: CarbonImmutable::parse('2026-02-10 00:00:00'),
        to: CarbonImmutable::parse('2026-02-11 23:59:59'),
    );

    expect($result->global)->toHaveKeys([
        'estimated_cost_usd',
        'estimated_dynamic_cost_usd',
        'estimated_style_editor_cost_usd',
        'estimated_document_analysis_cost_usd',
        'estimated_document_analyzer_cost_usd',
        'estimated_dedicated_extractor_cost_usd',
    ]);

    expect($result->global['first_pass_rate'])->toBe(50.0)
        ->and($result->global['retry_rate'])->toBe(25.0)
        ->and($result->global['failure_rate'])->toBe(25.0)
        ->and($result->global['avg_duration_ms'])->toBe(1625)
        ->and($result->global['p95_duration_ms'])->toBe(3000)
        ->and($result->global['estimated_cost_usd'])->toBe(0.5)
        ->and($result->global['estimated_dynamic_cost_usd'])->toBe(0.35)
        ->and($result->global['estimated_style_editor_cost_usd'])->toBe(0.15)
        ->and($result->global['analyzed_documents'])->toBe(1)
        ->and($result->global['estimated_document_analysis_cost_usd'])->toBe(0.09)
        ->and($result->global['estimated_document_analyzer_cost_usd'])->toBe(0.06)
        ->and($result->global['estimated_dedicated_extractor_cost_usd'])->toBe(0.03)
        ->and($result->memories[0]['technical_memory_id'])->toBe($memoryA->id)
        ->and($result->memories[0]['attempts'])->toBe(3)
        ->and($result->memories[0]['estimated_cost_usd'])->toBe(0.45)
        ->and($result->memories[0]['estimated_dynamic_cost_usd'])->toBe(0.31)
        ->and($result->memories[0]['estimated_style_editor_cost_usd'])->toBe(0.14)
        ->and($result->documentAnalysis['documents'])->toBe(1)
        ->and($result->documentAnalysis['estimated_cost_usd'])->toBe(0.09)
        ->and($result->topProblematicSections[0]['section_title'])->toBe('Section B');
});

it('filters metrics by date range', function (): void {
    $tender = Tender::factory()->create();
    $memory = TechnicalMemory::factory()->create(['tender_id' => $tender->id]);
    $section = TechnicalMemorySection::factory()->create(['technical_memory_id' => $memory->id]);

    TechnicalMemoryGenerationMetric::query()->forceCreate([
        'technical_memory_id' => $memory->id,
        'technical_memory_section_id' => $section->id,
        'run_id' => 'run-filter-1',
        'attempt' => 1,
        'status' => 'completed',
        'quality_passed' => true,
        'quality_reasons' => [],
        'duration_ms' => 1000,
        'output_chars' => 1800,
        'model_name' => 'gpt-5-mini',
        'estimated_input_units' => 0.001,
        'estimated_output_units' => 0.001,
        'estimated_cost_usd' => 0.2,
        'agent_cost_breakdown' => [
            'dynamic_section' => ['estimated_cost_usd' => 0.14],
            'style_editor' => ['estimated_cost_usd' => 0.06],
        ],
        'created_at' => CarbonImmutable::parse('2026-02-10 10:00:00'),
        'updated_at' => CarbonImmutable::parse('2026-02-10 10:00:00'),
    ]);

    TechnicalMemoryGenerationMetric::query()->forceCreate([
        'technical_memory_id' => $memory->id,
        'technical_memory_section_id' => $section->id,
        'run_id' => 'run-filter-2',
        'attempt' => 1,
        'status' => 'completed',
        'quality_passed' => true,
        'quality_reasons' => [],
        'duration_ms' => 900,
        'output_chars' => 1750,
        'model_name' => 'gpt-5-mini',
        'estimated_input_units' => 0.001,
        'estimated_output_units' => 0.001,
        'estimated_cost_usd' => 0.3,
        'agent_cost_breakdown' => [
            'dynamic_section' => ['estimated_cost_usd' => 0.21],
            'style_editor' => ['estimated_cost_usd' => 0.09],
        ],
        'created_at' => CarbonImmutable::parse('2026-02-11 10:00:00'),
        'updated_at' => CarbonImmutable::parse('2026-02-11 10:00:00'),
    ]);

    Document::factory()->create([
        'tender_id' => $tender->id,
        'document_type' => 'ppt',
        'status' => 'analyzed',
        'estimated_analysis_input_units' => 0.005,
        'estimated_analysis_output_units' => 0.002,
        'estimated_analysis_cost_usd' => 0.12,
        'analysis_cost_breakdown' => [
            'document_analyzer' => ['estimated_cost_usd' => 0.12],
            'dedicated_judgment_extractor' => ['estimated_cost_usd' => 0.0],
        ],
        'analyzed_at' => CarbonImmutable::parse('2026-02-10 08:00:00'),
    ]);

    Document::factory()->create([
        'tender_id' => $tender->id,
        'document_type' => 'pca',
        'status' => 'analyzed',
        'estimated_analysis_input_units' => 0.006,
        'estimated_analysis_output_units' => 0.002,
        'estimated_analysis_cost_usd' => 0.18,
        'analysis_cost_breakdown' => [
            'document_analyzer' => ['estimated_cost_usd' => 0.13],
            'dedicated_judgment_extractor' => ['estimated_cost_usd' => 0.05],
        ],
        'analyzed_at' => CarbonImmutable::parse('2026-02-11 08:00:00'),
    ]);

    $result = (new GetOperationalMetricsAction)(
        from: CarbonImmutable::parse('2026-02-11 00:00:00'),
        to: CarbonImmutable::parse('2026-02-11 23:59:59'),
    );

    expect($result->global)->toHaveKeys([
        'estimated_cost_usd',
        'estimated_dynamic_cost_usd',
        'estimated_style_editor_cost_usd',
        'estimated_document_analysis_cost_usd',
        'estimated_document_analyzer_cost_usd',
        'estimated_dedicated_extractor_cost_usd',
    ]);

    expect($result->global['attempts'])->toBe(1)
        ->and($result->global['estimated_cost_usd'])->toBe(0.3)
        ->and($result->global['estimated_dynamic_cost_usd'])->toBe(0.21)
        ->and($result->global['estimated_style_editor_cost_usd'])->toBe(0.09)
        ->and($result->global['analyzed_documents'])->toBe(1)
        ->and($result->global['estimated_document_analysis_cost_usd'])->toBe(0.18)
        ->and($result->global['estimated_document_analyzer_cost_usd'])->toBe(0.13)
        ->and($result->global['estimated_dedicated_extractor_cost_usd'])->toBe(0.05)
        ->and($result->dailyTrend)->toHaveCount(1);
});
