<?php

declare(strict_types=1);

use App\Actions\TechnicalMemories\GetOperationalMetricsAction;
use App\Enums\AiCostCategory;
use App\Models\AiCostEntry;
use App\Models\Document;
use App\Models\TechnicalMemory;
use App\Models\TechnicalMemoryGenerationMetric;
use App\Models\TechnicalMemorySection;
use App\Models\Tender;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('calculates global kpis durations and unified ai cost rollups', function (): void {
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

    $metricA1 = TechnicalMemoryGenerationMetric::query()->forceCreate([
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
        'created_at' => CarbonImmutable::parse('2026-02-10 10:00:00'),
        'updated_at' => CarbonImmutable::parse('2026-02-10 10:00:00'),
    ]);

    $metricA2 = TechnicalMemoryGenerationMetric::query()->forceCreate([
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
        'created_at' => CarbonImmutable::parse('2026-02-10 10:05:00'),
        'updated_at' => CarbonImmutable::parse('2026-02-10 10:05:00'),
    ]);

    $metricA3 = TechnicalMemoryGenerationMetric::query()->forceCreate([
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
        'created_at' => CarbonImmutable::parse('2026-02-11 11:00:00'),
        'updated_at' => CarbonImmutable::parse('2026-02-11 11:00:00'),
    ]);

    $metricB1 = TechnicalMemoryGenerationMetric::query()->forceCreate([
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
        'created_at' => CarbonImmutable::parse('2026-02-11 12:00:00'),
        'updated_at' => CarbonImmutable::parse('2026-02-11 12:00:00'),
    ]);

    $document = Document::factory()->create([
        'tender_id' => $tender->id,
        'document_type' => 'pca',
        'status' => 'analyzed',
        'analyzed_at' => CarbonImmutable::parse('2026-02-11 13:00:00'),
    ]);

    foreach ([
        [$metricA1, AiCostCategory::DynamicSection, 'dynamic_section', 0.07, '2026-02-10 10:00:00'],
        [$metricA1, AiCostCategory::StyleEditor, 'style_editor', 0.03, '2026-02-10 10:00:01'],
        [$metricA2, AiCostCategory::DynamicSection, 'dynamic_section', 0.14, '2026-02-10 10:05:00'],
        [$metricA2, AiCostCategory::StyleEditor, 'style_editor', 0.06, '2026-02-10 10:05:01'],
        [$metricA3, AiCostCategory::DynamicSection, 'dynamic_section', 0.10, '2026-02-11 11:00:00'],
        [$metricA3, AiCostCategory::StyleEditor, 'style_editor', 0.05, '2026-02-11 11:00:01'],
        [$metricB1, AiCostCategory::DynamicSection, 'dynamic_section', 0.04, '2026-02-11 12:00:00'],
        [$metricB1, AiCostCategory::StyleEditor, 'style_editor', 0.01, '2026-02-11 12:00:01'],
    ] as [$metric, $category, $agentKey, $costUsd, $createdAt]) {
        AiCostEntry::query()->forceCreate([
            'tender_id' => $tender->id,
            'technical_memory_id' => $metric->technical_memory_id,
            'technical_memory_section_id' => $metric->technical_memory_section_id,
            'technical_memory_generation_metric_id' => $metric->id,
            'run_id' => $metric->run_id,
            'attempt' => $metric->attempt,
            'category' => $category,
            'agent_key' => $agentKey,
            'model_name' => $metric->model_name,
            'status' => 'completed',
            'estimated_input_units' => 0.001,
            'estimated_output_units' => 0.001,
            'estimated_cost_usd' => $costUsd,
            'created_at' => CarbonImmutable::parse($createdAt),
            'updated_at' => CarbonImmutable::parse($createdAt),
        ]);
    }

    AiCostEntry::query()->forceCreate([
        'tender_id' => $tender->id,
        'document_id' => $document->id,
        'category' => AiCostCategory::DocumentAnalyzer,
        'agent_key' => 'document_analyzer',
        'model_name' => 'gpt-5.2',
        'status' => 'completed',
        'estimated_input_units' => 0.002,
        'estimated_output_units' => 0.001,
        'estimated_cost_usd' => 0.06,
        'created_at' => CarbonImmutable::parse('2026-02-11 13:00:00'),
        'updated_at' => CarbonImmutable::parse('2026-02-11 13:00:00'),
    ]);

    AiCostEntry::query()->forceCreate([
        'tender_id' => $tender->id,
        'document_id' => $document->id,
        'category' => AiCostCategory::DedicatedJudgmentExtractor,
        'agent_key' => 'dedicated_judgment_extractor',
        'model_name' => 'gpt-5-mini',
        'status' => 'completed',
        'estimated_input_units' => 0.001,
        'estimated_output_units' => 0.001,
        'estimated_cost_usd' => 0.03,
        'created_at' => CarbonImmutable::parse('2026-02-11 13:00:01'),
        'updated_at' => CarbonImmutable::parse('2026-02-11 13:00:01'),
    ]);

    $result = (new GetOperationalMetricsAction)(
        from: CarbonImmutable::parse('2026-02-10 00:00:00'),
        to: CarbonImmutable::parse('2026-02-11 23:59:59'),
    );

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

it('filters unified costs by date range', function (): void {
    $tender = Tender::factory()->create();
    $memory = TechnicalMemory::factory()->create(['tender_id' => $tender->id]);
    $section = TechnicalMemorySection::factory()->create(['technical_memory_id' => $memory->id]);

    $metric = TechnicalMemoryGenerationMetric::query()->forceCreate([
        'technical_memory_id' => $memory->id,
        'technical_memory_section_id' => $section->id,
        'run_id' => 'run-filter-1',
        'attempt' => 1,
        'status' => 'completed',
        'quality_passed' => true,
        'quality_reasons' => [],
        'duration_ms' => 900,
        'output_chars' => 1500,
        'model_name' => 'gpt-5-mini',
        'created_at' => CarbonImmutable::parse('2026-02-11 10:00:00'),
        'updated_at' => CarbonImmutable::parse('2026-02-11 10:00:00'),
    ]);

    $document = Document::factory()->create([
        'tender_id' => $tender->id,
        'document_type' => 'pca',
        'status' => 'analyzed',
        'analyzed_at' => CarbonImmutable::parse('2026-02-11 08:00:00'),
    ]);

    AiCostEntry::query()->forceCreate([
        'tender_id' => $tender->id,
        'technical_memory_id' => $memory->id,
        'technical_memory_section_id' => $section->id,
        'technical_memory_generation_metric_id' => $metric->id,
        'run_id' => 'run-filter-1',
        'attempt' => 1,
        'category' => AiCostCategory::DynamicSection,
        'agent_key' => 'dynamic_section',
        'model_name' => 'gpt-5-mini',
        'status' => 'completed',
        'estimated_input_units' => 0.001,
        'estimated_output_units' => 0.001,
        'estimated_cost_usd' => 0.21,
        'created_at' => CarbonImmutable::parse('2026-02-11 10:00:00'),
        'updated_at' => CarbonImmutable::parse('2026-02-11 10:00:00'),
    ]);

    AiCostEntry::query()->forceCreate([
        'tender_id' => $tender->id,
        'technical_memory_id' => $memory->id,
        'technical_memory_section_id' => $section->id,
        'technical_memory_generation_metric_id' => $metric->id,
        'run_id' => 'run-filter-1',
        'attempt' => 1,
        'category' => AiCostCategory::StyleEditor,
        'agent_key' => 'style_editor',
        'model_name' => 'gpt-5-mini',
        'status' => 'completed',
        'estimated_input_units' => 0.001,
        'estimated_output_units' => 0.001,
        'estimated_cost_usd' => 0.09,
        'created_at' => CarbonImmutable::parse('2026-02-11 10:00:01'),
        'updated_at' => CarbonImmutable::parse('2026-02-11 10:00:01'),
    ]);

    AiCostEntry::query()->forceCreate([
        'tender_id' => $tender->id,
        'document_id' => $document->id,
        'category' => AiCostCategory::DocumentAnalyzer,
        'agent_key' => 'document_analyzer',
        'model_name' => 'gpt-5.2',
        'status' => 'completed',
        'estimated_input_units' => 0.001,
        'estimated_output_units' => 0.001,
        'estimated_cost_usd' => 0.13,
        'created_at' => CarbonImmutable::parse('2026-02-11 08:00:00'),
        'updated_at' => CarbonImmutable::parse('2026-02-11 08:00:00'),
    ]);

    AiCostEntry::query()->forceCreate([
        'tender_id' => $tender->id,
        'document_id' => $document->id,
        'category' => AiCostCategory::DedicatedJudgmentExtractor,
        'agent_key' => 'dedicated_judgment_extractor',
        'model_name' => 'gpt-5-mini',
        'status' => 'completed',
        'estimated_input_units' => 0.001,
        'estimated_output_units' => 0.001,
        'estimated_cost_usd' => 0.05,
        'created_at' => CarbonImmutable::parse('2026-02-11 08:00:01'),
        'updated_at' => CarbonImmutable::parse('2026-02-11 08:00:01'),
    ]);

    $result = (new GetOperationalMetricsAction)(
        from: CarbonImmutable::parse('2026-02-11 00:00:00'),
        to: CarbonImmutable::parse('2026-02-11 23:59:59'),
    );

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

it('keeps unified cost totals in zero when there are no ai entries', function (): void {
    $tender = Tender::factory()->create();
    $memory = TechnicalMemory::factory()->create(['tender_id' => $tender->id]);
    $section = TechnicalMemorySection::factory()->create(['technical_memory_id' => $memory->id]);

    TechnicalMemoryGenerationMetric::query()->forceCreate([
        'technical_memory_id' => $memory->id,
        'technical_memory_section_id' => $section->id,
        'run_id' => 'run-empty',
        'attempt' => 1,
        'status' => 'completed',
        'quality_passed' => true,
        'quality_reasons' => [],
        'duration_ms' => 1000,
        'output_chars' => 1500,
        'model_name' => 'gpt-5-mini',
        'created_at' => CarbonImmutable::parse('2026-02-11 09:00:00'),
        'updated_at' => CarbonImmutable::parse('2026-02-11 09:00:00'),
    ]);

    Document::factory()->create([
        'tender_id' => $tender->id,
        'document_type' => 'pca',
        'status' => 'analyzed',
        'analyzed_at' => CarbonImmutable::parse('2026-02-11 10:00:00'),
    ]);

    $result = (new GetOperationalMetricsAction)(
        from: CarbonImmutable::parse('2026-02-11 00:00:00'),
        to: CarbonImmutable::parse('2026-02-11 23:59:59'),
    );

    expect($result->global['estimated_cost_usd'])->toBe(0.0)
        ->and($result->global['estimated_dynamic_cost_usd'])->toBe(0.0)
        ->and($result->global['estimated_style_editor_cost_usd'])->toBe(0.0)
        ->and($result->global['estimated_document_analysis_cost_usd'])->toBe(0.0)
        ->and($result->global['estimated_document_analyzer_cost_usd'])->toBe(0.0)
        ->and($result->global['estimated_dedicated_extractor_cost_usd'])->toBe(0.0);
});
