<?php

declare(strict_types=1);

use App\Ai\Agents\TechnicalMemoryDynamicSectionAgent;
use App\Ai\Agents\TechnicalMemorySectionEditorAgent;
use App\Data\TechnicalMemoryGenerationContextData;
use App\Data\TechnicalMemorySectionData;
use App\Enums\TechnicalMemorySectionStatus;
use App\Jobs\GenerateTechnicalMemorySection;
use App\Models\TechnicalMemoryMetricEvent;
use App\Models\TechnicalMemoryGenerationMetric;
use App\Models\TechnicalMemory;
use App\Models\TechnicalMemorySection;
use App\Models\Tender;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;

use function Pest\Laravel\assertDatabaseHas;

uses(RefreshDatabase::class);

it('generates a section and keeps memory in draft when pending sections remain', function (): void {
    $tender = Tender::factory()->create();

    $memory = TechnicalMemory::factory()->create([
        'tender_id' => $tender->id,
        'status' => 'draft',
        'generated_at' => null,
    ]);

    $section = TechnicalMemorySection::factory()->create([
        'technical_memory_id' => $memory->id,
        'section_title' => 'Metodología',
        'status' => 'pending',
    ]);

    TechnicalMemorySection::factory()->create([
        'technical_memory_id' => $memory->id,
        'section_title' => 'Equipo',
        'status' => 'pending',
    ]);

    $richMethodologyContent = "### Metodología propuesta\n\n"
        .str_repeat('Se define un enfoque iterativo con planificación por entregables verificables y criterios de aceptación. ', 12)
        ."\n\n### Flujo operativo\n\n"
        .str_repeat('Cada iteración incorpora análisis, diseño, validación funcional y control de impacto con evidencia documental. ', 10)
        ."\n\n### Indicadores y control\n\n"
        .str_repeat('Se incorporan métricas de avance, calidad y riesgo para asegurar trazabilidad y mejora continua durante la ejecución. ', 10);

    TechnicalMemoryDynamicSectionAgent::fake([
        ['content' => $richMethodologyContent],
    ])->preventStrayPrompts();

    TechnicalMemorySectionEditorAgent::fake([
        ['content' => $richMethodologyContent],
    ])->preventStrayPrompts();

    (new GenerateTechnicalMemorySection(
        technicalMemorySectionId: $section->id,
        section: TechnicalMemorySectionData::fromArray([
            'group_key' => '1.1-metodologia',
            'section_number' => '1.1',
            'section_title' => 'Metodología',
            'total_points' => 16,
            'criteria_count' => 1,
            'criteria' => [],
            'sort_key' => '0001.0001|metodología',
        ]),
        context: TechnicalMemoryGenerationContextData::fromArray([
            'pca' => ['criteria' => []],
            'ppt' => ['specifications' => []],
            'memory_title' => 'Memoria test',
        ]),
    ))->handle();

    $section = $section->fresh();
    $memory = $memory->fresh();

    expect($section)->not->toBeNull();
    expect($section?->status)->toBe(TechnicalMemorySectionStatus::Completed);
    expect($section?->content)->toContain('Metodología propuesta');
    expect($memory?->status)->toBe('draft');
    expect($memory?->generated_at)->toBeNull();

    $events = TechnicalMemoryMetricEvent::query()
        ->where('technical_memory_id', $memory->id)
        ->where('technical_memory_section_id', $section->id)
        ->orderBy('id')
        ->get();

    expect($events->pluck('event_type')->all())->toBe(['started', 'completed'])
        ->and($events->pluck('run_id')->unique()->count())->toBe(1)
        ->and($events->first()?->attempt)->toBe(1)
        ->and($events->last()?->attempt)->toBe(1);

    assertDatabaseHas('technical_memory_generation_metrics', [
        'technical_memory_id' => $memory->id,
        'technical_memory_section_id' => $section->id,
    ]);

    $metric = TechnicalMemoryGenerationMetric::query()
        ->where('technical_memory_id', $memory->id)
        ->where('technical_memory_section_id', $section->id)
        ->latest('id')
        ->first();

    expect($metric)->not->toBeNull()
        ->and($metric?->attempt)->toBe(1)
        ->and($metric?->duration_ms)->toBeGreaterThanOrEqual(0)
        ->and($metric?->quality_passed)->toBeTrue()
        ->and($metric?->output_chars)->toBeGreaterThan(0)
        ->and((float) $metric?->estimated_cost_usd)->toBeGreaterThan(0.0);
});

it('marks memory as generated when all dynamic sections finish', function (): void {
    $tender = Tender::factory()->create();

    $memory = TechnicalMemory::factory()->create([
        'tender_id' => $tender->id,
        'status' => 'draft',
        'generated_at' => null,
    ]);

    TechnicalMemorySection::factory()->create([
        'technical_memory_id' => $memory->id,
        'section_title' => 'Metodología',
        'status' => 'completed',
        'content' => 'Contenido previo',
    ]);

    $section = TechnicalMemorySection::factory()->create([
        'technical_memory_id' => $memory->id,
        'section_title' => 'Gobierno',
        'status' => 'pending',
        'content' => null,
    ]);

    $richGovernanceContent = "### Gobierno del servicio\n\n"
        .str_repeat('El modelo de gobernanza define responsables, cadencias de seguimiento y evidencias por cada hito contractual. ', 12)
        ."\n\n### Reporting y métricas\n\n"
        .str_repeat('Se establecen informes periódicos con indicadores operativos y de calidad para soportar decisiones de control. ', 10)
        ."\n\n### Gestión de incidencias\n\n"
        .str_repeat('El circuito de incidencias y riesgos incluye clasificación, tiempos objetivo, mitigación y cierre verificable. ', 10);

    TechnicalMemoryDynamicSectionAgent::fake([
        ['content' => $richGovernanceContent],
    ])->preventStrayPrompts();

    TechnicalMemorySectionEditorAgent::fake([
        ['content' => $richGovernanceContent],
    ])->preventStrayPrompts();

    (new GenerateTechnicalMemorySection(
        technicalMemorySectionId: $section->id,
        section: TechnicalMemorySectionData::fromArray([
            'group_key' => '2.1-gobierno',
            'section_number' => '2.1',
            'section_title' => 'Gobierno',
            'total_points' => 10,
            'criteria_count' => 1,
            'criteria' => [],
            'sort_key' => '0002.0001|gobierno',
        ]),
        context: TechnicalMemoryGenerationContextData::fromArray([
            'pca' => ['criteria' => []],
            'ppt' => ['specifications' => []],
            'memory_title' => 'Memoria test',
        ]),
    ))->handle();

    $memory = $memory->fresh();
    $section = $section->fresh();

    expect($section?->status)->toBe(TechnicalMemorySectionStatus::Completed);
    expect($section?->content)->toContain('Gobierno del servicio');
    expect($memory?->status)->toBe('generated');
    expect($memory?->generated_at)->not->toBeNull();
});

it('retries once when generated section does not meet quality gate', function (): void {
    Queue::fake();

    $tender = Tender::factory()->create();

    $memory = TechnicalMemory::factory()->create([
        'tender_id' => $tender->id,
        'status' => 'draft',
    ]);

    $section = TechnicalMemorySection::factory()->create([
        'technical_memory_id' => $memory->id,
        'section_title' => 'Metodología',
        'status' => 'pending',
        'content' => null,
    ]);

    TechnicalMemoryDynamicSectionAgent::fake([
        ['content' => '### Resumen breve\n\nTexto corto.'],
    ])->preventStrayPrompts();

    TechnicalMemorySectionEditorAgent::fake([
        ['content' => '### Resumen breve\n\nTexto corto.'],
    ])->preventStrayPrompts();

    (new GenerateTechnicalMemorySection(
        technicalMemorySectionId: $section->id,
        section: TechnicalMemorySectionData::fromArray([
            'group_key' => '2.1-metodologia',
            'section_number' => '2.1',
            'section_title' => 'Metodología',
            'total_points' => 6,
            'criteria_count' => 1,
            'criteria' => [],
            'sort_key' => '0002.0001|metodologia',
        ]),
        context: TechnicalMemoryGenerationContextData::fromArray([
            'pca' => ['criteria' => []],
            'ppt' => ['specifications' => []],
            'memory_title' => 'Memoria test',
        ]),
    ))->handle();

    $section = $section->fresh();

    expect($section?->status)->toBe(TechnicalMemorySectionStatus::Pending);
    expect($section?->error_message)->not->toBeNull();

    Queue::assertPushed(GenerateTechnicalMemorySection::class, function (GenerateTechnicalMemorySection $job): bool {
        return $job->technicalMemorySectionId > 0
            && $job->qualityAttempt === 1
            && $job->context->qualityFeedback !== null
            && is_string($job->context->runId)
            && $job->context->runId !== '';
    });

    $metric = TechnicalMemoryGenerationMetric::query()
        ->where('technical_memory_id', $memory->id)
        ->where('technical_memory_section_id', $section->id)
        ->latest('id')
        ->first();

    expect($metric)->not->toBeNull()
        ->and($metric?->attempt)->toBe(1)
        ->and($metric?->duration_ms)->toBeGreaterThanOrEqual(0)
        ->and($metric?->quality_passed)->toBeFalse()
        ->and($metric?->output_chars)->toBeGreaterThan(0)
        ->and((float) $metric?->estimated_cost_usd)->toBeGreaterThan(0.0);
});
