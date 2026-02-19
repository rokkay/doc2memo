<?php

declare(strict_types=1);

use App\Actions\Tenders\GenerateTechnicalMemoryAction;
use App\Ai\Agents\TechnicalMemoryDynamicSectionAgent;
use App\Ai\Agents\TechnicalMemorySectionEditorAgent;
use App\Jobs\GenerateTechnicalMemorySection;
use App\Models\Document;
use App\Models\ExtractedCriterion;
use App\Models\Tender;
use Illuminate\Bus\PendingBatch;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;

use function Pest\Laravel\assertDatabaseHas;

uses(RefreshDatabase::class);

it('aggregates completed counters into a run summary after all sections finish', function (): void {
    Bus::fake();

    $tender = Tender::factory()->completed()->create([
        'title' => 'Servicio de soporte integral',
    ]);

    $pcaDocument = Document::factory()->create([
        'tender_id' => $tender->id,
        'document_type' => 'pca',
    ]);

    ExtractedCriterion::factory()->create([
        'tender_id' => $tender->id,
        'document_id' => $pcaDocument->id,
        'section_number' => '1.1',
        'section_title' => 'Metodología',
        'description' => 'Criterio metodológico sin subapartados.',
        'group_key' => '1.1-metodologia',
        'criterion_type' => 'judgment',
        'score_points' => 16,
    ]);

    ExtractedCriterion::factory()->create([
        'tender_id' => $tender->id,
        'document_id' => $pcaDocument->id,
        'section_number' => '2.1',
        'section_title' => 'Gobierno',
        'description' => 'Criterio de gobierno sin subapartados.',
        'group_key' => '2.1-gobierno',
        'criterion_type' => 'judgment',
        'score_points' => 12,
    ]);

    $richContent = "### Enfoque metodologico\n\n"
        .str_repeat('Se define un plan iterativo con entregables verificables y criterios de aceptacion. ', 12)
        ."\n\n### Operacion\n\n"
        .str_repeat('La operacion incorpora seguimiento continuo, control de riesgos y trazabilidad documental. ', 10)
        ."\n\n### Calidad\n\n"
        .str_repeat('El sistema de calidad utiliza metricas objetivas para validar avance, cumplimiento y mejora continua. ', 10);

    TechnicalMemoryDynamicSectionAgent::fake([
        ['content' => $richContent],
        ['content' => $richContent],
    ])->preventStrayPrompts();

    TechnicalMemorySectionEditorAgent::fake([
        ['content' => $richContent],
        ['content' => $richContent],
    ])->preventStrayPrompts();

    resolve(GenerateTechnicalMemoryAction::class)($tender);

    $jobs = collect();

    Bus::assertBatched(function (PendingBatch $batch) use (&$jobs): bool {
        $jobs = $batch->jobs
            ->filter(fn ($job): bool => $job instanceof GenerateTechnicalMemorySection)
            ->values();

        return $jobs->count() === 2;
    });

    expect($jobs)->toHaveCount(2);

    foreach ($jobs as $job) {
        $job->handle();
    }

    $runId = (string) $jobs[0]->context->runId;

    assertDatabaseHas('technical_memory_metric_runs', [
        'technical_memory_id' => $tender->fresh()->technicalMemory?->id,
        'run_id' => $runId,
        'trigger' => 'full_generation',
        'status' => 'completed',
        'sections_total' => 2,
        'sections_completed' => 2,
        'sections_failed' => 0,
        'sections_retried' => 0,
    ]);
});
