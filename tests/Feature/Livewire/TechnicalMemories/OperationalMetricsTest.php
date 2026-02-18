<?php

declare(strict_types=1);

use App\Enums\AiCostCategory;
use App\Livewire\TechnicalMemories\OperationalMetrics;
use App\Models\AiCostEntry;
use App\Models\Document;
use App\Models\TechnicalMemory;
use App\Models\TechnicalMemoryGenerationMetric;
use App\Models\TechnicalMemorySection;
use App\Models\Tender;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses()->group('livewire');
uses(RefreshDatabase::class);

it('renders kpi cards and metrics tables with unified ai costs', function (): void {
    $tender = Tender::factory()->create();
    $memory = TechnicalMemory::factory()->create([
        'tender_id' => $tender->id,
        'title' => 'Memory Dashboard A',
    ]);
    $section = TechnicalMemorySection::factory()->create([
        'technical_memory_id' => $memory->id,
        'section_title' => 'Section Problematic',
    ]);

    $metricA = TechnicalMemoryGenerationMetric::query()->forceCreate([
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
        'created_at' => CarbonImmutable::parse('2026-02-12 10:00:00'),
        'updated_at' => CarbonImmutable::parse('2026-02-12 10:00:00'),
    ]);

    $metricB = TechnicalMemoryGenerationMetric::query()->forceCreate([
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
        'created_at' => CarbonImmutable::parse('2026-02-12 10:05:00'),
        'updated_at' => CarbonImmutable::parse('2026-02-12 10:05:00'),
    ]);

    $document = Document::factory()->create([
        'tender_id' => $tender->id,
        'document_type' => 'pca',
        'status' => 'analyzed',
        'analyzed_at' => CarbonImmutable::parse('2026-02-12 10:06:00'),
    ]);

    foreach ([
        [$metricA, AiCostCategory::DynamicSection, 'dynamic_section', 0.15, '2026-02-12 10:00:00'],
        [$metricA, AiCostCategory::StyleEditor, 'style_editor', 0.05, '2026-02-12 10:00:01'],
        [$metricB, AiCostCategory::DynamicSection, 'dynamic_section', 0.19, '2026-02-12 10:05:00'],
        [$metricB, AiCostCategory::StyleEditor, 'style_editor', 0.06, '2026-02-12 10:05:01'],
    ] as [$metric, $category, $agentKey, $costUsd, $createdAt]) {
        AiCostEntry::query()->forceCreate([
            'tender_id' => $tender->id,
            'technical_memory_id' => $memory->id,
            'technical_memory_section_id' => $section->id,
            'technical_memory_generation_metric_id' => $metric->id,
            'run_id' => 'dashboard-run-1',
            'attempt' => $metric->attempt,
            'category' => $category,
            'agent_key' => $agentKey,
            'model_name' => 'gpt-5-mini',
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
        'estimated_input_units' => 0.001,
        'estimated_output_units' => 0.001,
        'estimated_cost_usd' => 0.06,
        'created_at' => CarbonImmutable::parse('2026-02-12 10:06:00'),
        'updated_at' => CarbonImmutable::parse('2026-02-12 10:06:00'),
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
        'created_at' => CarbonImmutable::parse('2026-02-12 10:06:01'),
        'updated_at' => CarbonImmutable::parse('2026-02-12 10:06:01'),
    ]);

    Livewire::test(OperationalMetrics::class)
        ->assertSee('First pass')
        ->assertSee('Retry')
        ->assertSee('Failure')
        ->assertSee('Duracion media')
        ->assertSee('Coste AI unificado')
        ->assertDontSee('Coste generacion')
        ->assertDontSee('Desglose por flujo')
        ->assertSee('Documentos analizados: 1')
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

    $metricA = TechnicalMemoryGenerationMetric::query()->forceCreate([
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
        'created_at' => CarbonImmutable::parse('2026-02-12 10:00:00'),
        'updated_at' => CarbonImmutable::parse('2026-02-12 10:00:00'),
    ]);

    $metricB = TechnicalMemoryGenerationMetric::query()->forceCreate([
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
        'created_at' => CarbonImmutable::parse('2026-02-13 10:00:00'),
        'updated_at' => CarbonImmutable::parse('2026-02-13 10:00:00'),
    ]);

    $document = Document::factory()->create([
        'tender_id' => $tender->id,
        'document_type' => 'pca',
        'status' => 'analyzed',
        'analyzed_at' => CarbonImmutable::parse('2026-02-13 10:30:00'),
    ]);

    foreach ([
        [$metricA, AiCostCategory::DynamicSection, 'dynamic_section', 0.08, '2026-02-12 10:00:00'],
        [$metricA, AiCostCategory::StyleEditor, 'style_editor', 0.03, '2026-02-12 10:00:01'],
        [$metricB, AiCostCategory::DynamicSection, 'dynamic_section', 0.16, '2026-02-13 10:00:00'],
        [$metricB, AiCostCategory::StyleEditor, 'style_editor', 0.06, '2026-02-13 10:00:01'],
    ] as [$metric, $category, $agentKey, $costUsd, $createdAt]) {
        AiCostEntry::query()->forceCreate([
            'tender_id' => $tender->id,
            'technical_memory_id' => $memory->id,
            'technical_memory_section_id' => $section->id,
            'technical_memory_generation_metric_id' => $metric->id,
            'run_id' => $metric->run_id,
            'attempt' => $metric->attempt,
            'category' => $category,
            'agent_key' => $agentKey,
            'model_name' => 'gpt-5-mini',
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
        'estimated_input_units' => 0.001,
        'estimated_output_units' => 0.001,
        'estimated_cost_usd' => 0.13,
        'created_at' => CarbonImmutable::parse('2026-02-13 10:30:00'),
        'updated_at' => CarbonImmutable::parse('2026-02-13 10:30:00'),
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
        'created_at' => CarbonImmutable::parse('2026-02-13 10:30:01'),
        'updated_at' => CarbonImmutable::parse('2026-02-13 10:30:01'),
    ]);

    Livewire::test(OperationalMetrics::class)
        ->assertSet('metrics.global.attempts', 2)
        ->set('from_date', '2026-02-13')
        ->set('to_date', '2026-02-13')
        ->assertSet('metrics.global.attempts', 1)
        ->assertSet('metrics.global.estimated_total_ai_cost_usd', 0.4)
        ->assertSet('metrics.global.estimated_cost_usd', 0.22)
        ->assertSet('metrics.global.estimated_dynamic_cost_usd', 0.16)
        ->assertSet('metrics.global.estimated_style_editor_cost_usd', 0.06)
        ->assertSet('metrics.global.analyzed_documents', 1)
        ->assertSet('metrics.global.estimated_document_analysis_cost_usd', 0.18)
        ->assertSet('metrics.global.estimated_document_analyzer_cost_usd', 0.13)
        ->assertSet('metrics.global.estimated_dedicated_extractor_cost_usd', 0.05);
});

it('keeps cost cards in zero when there are no ai cost entries', function (): void {
    $tender = Tender::factory()->create();
    $memory = TechnicalMemory::factory()->create(['tender_id' => $tender->id]);
    $section = TechnicalMemorySection::factory()->create(['technical_memory_id' => $memory->id]);

    TechnicalMemoryGenerationMetric::query()->forceCreate([
        'technical_memory_id' => $memory->id,
        'technical_memory_section_id' => $section->id,
        'run_id' => 'dashboard-empty-1',
        'attempt' => 1,
        'status' => 'completed',
        'quality_passed' => true,
        'quality_reasons' => [],
        'duration_ms' => 1000,
        'output_chars' => 1400,
        'model_name' => 'gpt-5-mini',
        'created_at' => CarbonImmutable::parse('2026-02-11 10:00:00'),
        'updated_at' => CarbonImmutable::parse('2026-02-11 10:00:00'),
    ]);

    Document::factory()->create([
        'tender_id' => $tender->id,
        'document_type' => 'pca',
        'status' => 'analyzed',
        'analyzed_at' => CarbonImmutable::parse('2026-02-11 10:05:00'),
    ]);

    Livewire::test(OperationalMetrics::class)
        ->set('from_date', '2026-02-11')
        ->set('to_date', '2026-02-11')
        ->assertSet('metrics.global.estimated_total_ai_cost_usd', 0.0)
        ->assertSet('metrics.global.estimated_cost_usd', 0.0)
        ->assertSet('metrics.global.estimated_dynamic_cost_usd', 0.0)
        ->assertSet('metrics.global.estimated_style_editor_cost_usd', 0.0)
        ->assertSet('metrics.global.estimated_document_analysis_cost_usd', 0.0)
        ->assertSet('metrics.global.estimated_document_analyzer_cost_usd', 0.0)
        ->assertSet('metrics.global.estimated_dedicated_extractor_cost_usd', 0.0);
});
