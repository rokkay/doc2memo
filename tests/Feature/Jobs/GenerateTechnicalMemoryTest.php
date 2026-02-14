<?php

declare(strict_types=1);

use App\Data\TechnicalMemoryGenerationContextData;
use App\Data\TechnicalMemorySectionData;
use App\Jobs\GenerateTechnicalMemory;
use App\Jobs\GenerateTechnicalMemorySection;
use App\Models\Document;
use App\Models\ExtractedCriterion;
use App\Models\ExtractedSpecification;
use App\Models\Tender;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;

use function Pest\Laravel\assertDatabaseHas;

uses(RefreshDatabase::class);

it('creates dynamic draft memory and dispatches one job per judgment section', function (): void {
    Queue::fake();

    $tender = Tender::factory()->completed()->create([
        'title' => 'Servicio de desarrollo y mantenimiento',
    ]);

    $pcaDocument = Document::factory()->create([
        'tender_id' => $tender->id,
        'document_type' => 'pca',
    ]);

    $pptDocument = Document::factory()->create([
        'tender_id' => $tender->id,
        'document_type' => 'ppt',
    ]);

    $firstCriterion = ExtractedCriterion::factory()->create([
        'tender_id' => $tender->id,
        'document_id' => $pcaDocument->id,
        'section_number' => '8.2',
        'section_title' => 'Criterios adjudicación (B) Juicio de valor - Metodología',
        'group_key' => '8.2-metodologia',
        'description' => 'Propuesta metodológica orientada a valor.',
        'priority' => 'mandatory',
        'criterion_type' => 'judgment',
        'score_points' => 25,
    ]);

    ExtractedCriterion::factory()->create([
        'tender_id' => $tender->id,
        'document_id' => $pcaDocument->id,
        'section_number' => '8.2',
        'section_title' => 'Criterios adjudicación (B) Juicio de valor - Metodología',
        'group_key' => '8.2-metodologia',
        'description' => 'Detalle de riesgos y mitigación.',
        'priority' => 'preferable',
        'criterion_type' => 'judgment',
        'score_points' => 15,
    ]);

    ExtractedCriterion::factory()->create([
        'tender_id' => $tender->id,
        'document_id' => $pcaDocument->id,
        'section_number' => '2.3',
        'section_title' => 'Gobierno del servicio',
        'group_key' => '2.3-gobierno-del-servicio',
        'description' => 'Modelo de reporting y seguimiento.',
        'priority' => 'mandatory',
        'criterion_type' => 'judgment',
        'score_points' => 20,
    ]);

    ExtractedCriterion::factory()->create([
        'tender_id' => $tender->id,
        'document_id' => $pcaDocument->id,
        'section_number' => '10',
        'section_title' => 'Oferta económica',
        'group_key' => '10-oferta-economica',
        'description' => 'Precio más bajo.',
        'priority' => 'mandatory',
        'criterion_type' => 'automatic',
        'score_points' => 40,
    ]);

    ExtractedSpecification::factory()->create([
        'tender_id' => $tender->id,
        'document_id' => $pptDocument->id,
        'section_title' => 'Arquitectura',
        'technical_description' => 'Migración a Drupal actualizado con enfoque accesible.',
        'requirements' => 'Cumplir WCAG 2.1 AA',
    ]);

    (new GenerateTechnicalMemory($tender))->handle();

    assertDatabaseHas('technical_memories', [
        'tender_id' => $tender->id,
        'status' => 'draft',
        'title' => 'Memoria Técnica - '.$tender->title,
    ]);

    assertDatabaseHas('technical_memory_sections', [
        'section_title' => 'Criterios adjudicación (B) Juicio de valor - Metodología',
        'section_number' => '8.2',
        'total_points' => 40.00,
        'criteria_count' => 2,
        'sort_order' => 2,
    ]);

    assertDatabaseHas('technical_memory_sections', [
        'section_title' => 'Gobierno del servicio',
        'total_points' => 20.00,
        'criteria_count' => 1,
        'sort_order' => 1,
    ]);

    Queue::assertPushedTimes(GenerateTechnicalMemorySection::class, 2);

    Queue::assertPushed(GenerateTechnicalMemorySection::class, function (GenerateTechnicalMemorySection $job): bool {
        return $job->section instanceof TechnicalMemorySectionData
            && $job->context instanceof TechnicalMemoryGenerationContextData
            && $job->section->sectionTitle === 'Criterios adjudicación (B) Juicio de valor - Metodología'
            && $job->section->totalPoints === 40.0
            && count($job->section->criteria) === 2
            && data_get($job->context->pca, 'criteria.0.criterion_type') === 'judgment';
    });

    $firstSection = $tender->fresh()->technicalMemory?->sections()->orderBy('sort_order')->first();

    expect($firstSection)->not->toBeNull();
    expect($firstSection?->section_title)->toBe('Gobierno del servicio');
    expect($tender->fresh()->technicalMemory?->sections()->where('group_key', $firstCriterion->group_key)->exists())->toBeTrue();
});

it('splits a grouped judgment criterion into multiple dynamic sections', function (): void {
    Queue::fake();

    $tender = Tender::factory()->completed()->create();

    $pcaDocument = Document::factory()->create([
        'tender_id' => $tender->id,
        'document_type' => 'pca',
    ]);

    ExtractedCriterion::factory()->create([
        'tender_id' => $tender->id,
        'document_id' => $pcaDocument->id,
        'section_number' => 'Cuadro criterios adjudicación',
        'section_title' => 'Criterios de adjudicación (100 puntos)',
        'description' => 'Juicio de valor 50: 1.1 evolución funcional 16; 1.2 tecnológica 10; 1.3 plan ejecución 4; 2.1 metodología 6; 2.2 organización del equipo 8; 2.4 seguimiento y control 4.',
        'priority' => 'mandatory',
        'criterion_type' => 'judgment',
        'score_points' => 50,
    ]);

    (new GenerateTechnicalMemory($tender))->handle();

    expect($tender->fresh()->technicalMemory?->sections()->count())->toBe(6);

    assertDatabaseHas('technical_memory_sections', [
        'section_number' => '1.1',
        'section_title' => 'Evolución Funcional',
        'total_points' => 16.00,
    ]);

    assertDatabaseHas('technical_memory_sections', [
        'section_number' => '2.2',
        'section_title' => 'Organización Del Equipo',
        'total_points' => 8.00,
    ]);

    Queue::assertPushedTimes(GenerateTechnicalMemorySection::class, 6);
});

it('propagates one run id to every section generation job in a full generation', function (): void {
    Queue::fake();

    $tender = Tender::factory()->completed()->create();

    $pcaDocument = Document::factory()->create([
        'tender_id' => $tender->id,
        'document_type' => 'pca',
    ]);

    ExtractedCriterion::factory()->create([
        'tender_id' => $tender->id,
        'document_id' => $pcaDocument->id,
        'section_number' => '1.1',
        'section_title' => 'Metodología',
        'group_key' => '1.1-metodologia',
        'criterion_type' => 'judgment',
        'score_points' => 16,
    ]);

    ExtractedCriterion::factory()->create([
        'tender_id' => $tender->id,
        'document_id' => $pcaDocument->id,
        'section_number' => '2.1',
        'section_title' => 'Gobierno',
        'group_key' => '2.1-gobierno',
        'criterion_type' => 'judgment',
        'score_points' => 12,
    ]);

    (new GenerateTechnicalMemory($tender))->handle();

    $jobs = Queue::pushed(GenerateTechnicalMemorySection::class);

    $runIds = $jobs
        ->map(fn (GenerateTechnicalMemorySection $queuedJob): ?string => $queuedJob->runId)
        ->filter(fn (?string $runId): bool => is_string($runId) && $runId !== '')
        ->unique()
        ->values();

    expect($jobs)->toHaveCount(2)
        ->and($runIds)->toHaveCount(1)
        ->and($runIds->first())->not->toBe('')
        ->and($jobs->first()->runId)->toBe($jobs->last()->runId)
        ->and($jobs->first()->context->runId)->toBe($jobs->first()->runId);
});
