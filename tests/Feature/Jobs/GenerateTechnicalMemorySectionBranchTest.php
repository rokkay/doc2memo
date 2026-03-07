<?php

declare(strict_types=1);

use App\Ai\Agents\TechnicalMemoryDynamicSectionAgent;
use App\Ai\Agents\TechnicalMemorySectionEditorAgent;
use App\Data\TechnicalMemoryGenerationContextData;
use App\Data\TechnicalMemorySectionData;
use App\Enums\TechnicalMemorySectionStatus;
use App\Jobs\GenerateTechnicalMemorySection;
use App\Models\AiCostEntry;
use App\Models\TechnicalMemory;
use App\Models\TechnicalMemorySection;
use App\Models\Tender;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('returns early when batch is cancelled', function (): void {
    $tender = Tender::factory()->create();
    $memory = TechnicalMemory::factory()->create(['tender_id' => $tender->id]);
    $section = TechnicalMemorySection::factory()->create([
        'technical_memory_id' => $memory->id,
        'status' => 'pending',
    ]);

    [$job] = new GenerateTechnicalMemorySection(
        technicalMemorySectionId: $section->id,
        section: TechnicalMemorySectionData::fromArray([
            'group_key' => 'g',
            'section_number' => '1.1',
            'section_title' => 'Titulo',
            'total_points' => 1,
            'criteria_count' => 1,
            'criteria' => [],
            'sort_key' => '0001',
        ]),
        context: TechnicalMemoryGenerationContextData::fromArray([
            'pca' => ['criteria' => []],
            'ppt' => ['specifications' => []],
            'memory_title' => 'M',
        ]),
    )->withFakeBatch(cancelledAt: CarbonImmutable::now());

    $job->handle();

    expect($section->fresh()->status)->toBe(TechnicalMemorySectionStatus::Pending);
});

it('returns early when section cannot be loaded', function (): void {
    $job = new GenerateTechnicalMemorySection(
        technicalMemorySectionId: 999999,
        section: TechnicalMemorySectionData::fromArray([
            'group_key' => 'g',
            'section_number' => '1.1',
            'section_title' => 'Titulo',
            'total_points' => 1,
            'criteria_count' => 1,
            'criteria' => [],
            'sort_key' => '0001',
        ]),
        context: TechnicalMemoryGenerationContextData::fromArray([
            'pca' => ['criteria' => []],
            'ppt' => ['specifications' => []],
            'memory_title' => 'M',
        ]),
    );

    $job->handle();

    expect(true)->toBeTrue();
});

it('requeues retry inside same batch when quality fails', function (): void {
    config()->set('technical_memory.quality_gate.max_retry_attempts', 1);

    $tender = Tender::factory()->create();
    $memory = TechnicalMemory::factory()->create(['tender_id' => $tender->id]);
    $section = TechnicalMemorySection::factory()->create([
        'technical_memory_id' => $memory->id,
        'status' => 'pending',
    ]);

    TechnicalMemoryDynamicSectionAgent::fake([
        ['content' => '### Breve\n\nMuy corto.'],
    ])->preventStrayPrompts();

    TechnicalMemorySectionEditorAgent::fake([
        ['content' => '### Breve\n\nMuy corto.'],
    ])->preventStrayPrompts();

    [$job, $fakeBatch] = new GenerateTechnicalMemorySection(
        technicalMemorySectionId: $section->id,
        section: TechnicalMemorySectionData::fromArray([
            'group_key' => 'g',
            'section_number' => '1.1',
            'section_title' => 'Titulo',
            'total_points' => 1,
            'criteria_count' => 1,
            'criteria' => [],
            'sort_key' => '0001',
        ]),
        context: TechnicalMemoryGenerationContextData::fromArray([
            'pca' => ['criteria' => []],
            'ppt' => ['specifications' => []],
            'memory_title' => 'M',
        ]),
    )->withFakeBatch();

    $job->handle();

    expect($fakeBatch->added)->toHaveCount(1);
});

it('marks section failed and rethrows when dynamic generation throws', function (): void {
    $tender = Tender::factory()->create();
    $memory = TechnicalMemory::factory()->create(['tender_id' => $tender->id]);
    $section = TechnicalMemorySection::factory()->create([
        'technical_memory_id' => $memory->id,
        'status' => 'pending',
    ]);

    TechnicalMemoryDynamicSectionAgent::fake([
        fn (): never => throw new RuntimeException('dynamic exploded'),
    ])->preventStrayPrompts();

    expect(fn () => new GenerateTechnicalMemorySection(
        technicalMemorySectionId: $section->id,
        section: TechnicalMemorySectionData::fromArray([
            'group_key' => 'g',
            'section_number' => '1.1',
            'section_title' => 'Titulo',
            'total_points' => 1,
            'criteria_count' => 1,
            'criteria' => [],
            'sort_key' => '0001',
        ]),
        context: TechnicalMemoryGenerationContextData::fromArray([
            'pca' => ['criteria' => []],
            'ppt' => ['specifications' => []],
            'memory_title' => 'M',
        ]),
    )->handle())->toThrow(RuntimeException::class, 'dynamic exploded');

    expect($section->fresh()?->status)->toBe(TechnicalMemorySectionStatus::Failed)
        ->and($section->fresh()?->error_message)->toBe('dynamic exploded');
});

it('returns after completion when memory is no longer draft', function (): void {
    $tender = Tender::factory()->create();
    $memory = TechnicalMemory::factory()->create([
        'tender_id' => $tender->id,
        'status' => 'generated',
        'generated_at' => now(),
    ]);
    $section = TechnicalMemorySection::factory()->create([
        'technical_memory_id' => $memory->id,
        'status' => 'pending',
    ]);

    $richContent = "### Seccion\n\n".str_repeat('Texto suficientemente largo para superar calidad. ', 24)
        ."\n\n### Evidencias\n\n".str_repeat('Compromisos verificables y medibles para evaluación. ', 18)
        ."\n\n### Plan\n\n".str_repeat('Plan operativo con hitos, seguimiento y control de riesgos. ', 18);

    TechnicalMemoryDynamicSectionAgent::fake([
        ['content' => $richContent],
    ])->preventStrayPrompts();

    TechnicalMemorySectionEditorAgent::fake([
        ['content' => $richContent],
    ])->preventStrayPrompts();

    new GenerateTechnicalMemorySection(
        technicalMemorySectionId: $section->id,
        section: TechnicalMemorySectionData::fromArray([
            'group_key' => 'g',
            'section_number' => '1.1',
            'section_title' => 'Titulo',
            'total_points' => 1,
            'criteria_count' => 1,
            'criteria' => [],
            'sort_key' => '0001',
        ]),
        context: TechnicalMemoryGenerationContextData::fromArray([
            'pca' => ['criteria' => []],
            'ppt' => ['specifications' => []],
            'memory_title' => 'M',
        ]),
    )->handle();

    expect($memory->fresh()?->status)->toBe('generated')
        ->and($section->fresh()?->status)->toBe(TechnicalMemorySectionStatus::Completed);
});

it('reuses context run id when explicit job run id is empty', function (): void {
    $tender = Tender::factory()->create();
    $memory = TechnicalMemory::factory()->create(['tender_id' => $tender->id, 'status' => 'draft']);
    $section = TechnicalMemorySection::factory()->create([
        'technical_memory_id' => $memory->id,
        'status' => 'pending',
    ]);

    $richContent = "### Seccion\n\n".str_repeat('Texto de prueba con suficiente longitud. ', 30)
        ."\n\n### Evidencia\n\n".str_repeat('Metrica verificable y compromiso operativo. ', 20)
        ."\n\n### Plan\n\n".str_repeat('Plan detallado de ejecucion y control. ', 20);

    TechnicalMemoryDynamicSectionAgent::fake([
        ['content' => $richContent],
    ])->preventStrayPrompts();

    TechnicalMemorySectionEditorAgent::fake([
        ['content' => $richContent],
    ])->preventStrayPrompts();

    new GenerateTechnicalMemorySection(
        technicalMemorySectionId: $section->id,
        section: TechnicalMemorySectionData::fromArray([
            'group_key' => 'g',
            'section_number' => '1.1',
            'section_title' => 'Titulo',
            'total_points' => 1,
            'criteria_count' => 1,
            'criteria' => [],
            'sort_key' => '0001',
        ]),
        context: TechnicalMemoryGenerationContextData::fromArray([
            'pca' => ['criteria' => []],
            'ppt' => ['specifications' => []],
            'memory_title' => 'M',
            'run_id' => 'context-run-id',
        ]),
    )->handle();

    $freshMemory = $memory->fresh();

    expect($freshMemory)->not->toBeNull();

    $metric = $freshMemory->generationMetrics()->latest('id')->first();

    expect($metric?->run_id)->toBe('context-run-id');
});

it('covers even median and ai cost breakdown guard branches via reflection', function (): void {
    $tender = Tender::factory()->create();
    $memory = TechnicalMemory::factory()->create(['tender_id' => $tender->id]);
    $section = TechnicalMemorySection::factory()->create([
        'technical_memory_id' => $memory->id,
        'status' => 'completed',
        'content' => str_repeat('A', 100),
    ]);

    TechnicalMemorySection::factory()->create([
        'technical_memory_id' => $memory->id,
        'status' => 'completed',
        'content' => str_repeat('B', 200),
    ]);

    $job = new GenerateTechnicalMemorySection(
        technicalMemorySectionId: $section->id,
        section: TechnicalMemorySectionData::fromArray([
            'group_key' => 'g',
            'section_number' => '1.1',
            'section_title' => 'Titulo',
            'total_points' => 1,
            'criteria_count' => 1,
            'criteria' => [],
            'sort_key' => '0001',
        ]),
        context: TechnicalMemoryGenerationContextData::fromArray([
            'pca' => ['criteria' => []],
            'ppt' => ['specifications' => []],
            'memory_title' => 'M',
        ]),
    );

    $median = new ReflectionMethod(GenerateTechnicalMemorySection::class, 'medianCompletedSectionLength');

    expect($median->invoke($job, $memory->id, 999999))->toBe(150);

    $metric = $memory->generationMetrics()->create([
        'technical_memory_id' => $memory->id,
        'technical_memory_section_id' => $section->id,
        'run_id' => 'run-id',
        'attempt' => 1,
        'status' => 'completed',
        'quality_passed' => true,
        'quality_reasons' => [],
        'duration_ms' => 1,
        'output_chars' => 10,
        'model_name' => 'gpt-test',
    ]);

    $storeAiCostEntries = new ReflectionMethod(GenerateTechnicalMemorySection::class, 'storeAiCostEntries');

    $storeAiCostEntries->invoke(
        $job,
        $memory,
        $section,
        $metric,
        'run-id',
        1,
        [
            'dynamic_section' => 'invalid',
            'style_editor' => [
                'model_name' => 'gpt-test',
                'status' => 'completed',
                'input_chars' => 12,
                'output_chars' => 8,
                'token_usage' => 'invalid',
            ],
        ],
    );

    $entry = AiCostEntry::query()
        ->where('technical_memory_id', $memory->id)
        ->where('agent_key', 'style_editor')
        ->latest('id')
        ->first();

    expect($entry)->not->toBeNull()
        ->and($entry?->prompt_tokens)->toBe(0)
        ->and($entry?->completion_tokens)->toBe(0);
});
