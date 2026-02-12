<?php

declare(strict_types=1);

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

it('creates draft memory and dispatches one section job per section', function (): void {
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

    ExtractedCriterion::factory()->create([
        'tender_id' => $tender->id,
        'document_id' => $pcaDocument->id,
        'section_title' => 'Criterio de solvencia',
        'description' => "1) Experiencia demostrable en proyectos similares.\n2) Equipo con perfiles certificados.",
        'priority' => 'mandatory',
    ]);

    ExtractedSpecification::factory()->create([
        'tender_id' => $tender->id,
        'document_id' => $pptDocument->id,
        'section_title' => 'Arquitectura',
        'technical_description' => 'Migracion a Drupal actualizado con enfoque accesible.',
        'requirements' => 'Cumplir WCAG 2.1 AA',
    ]);

    (new GenerateTechnicalMemory($tender))->handle();

    assertDatabaseHas('technical_memories', [
        'tender_id' => $tender->id,
        'status' => 'draft',
        'title' => 'Memoria Técnica - '.$tender->title,
    ]);

    Queue::assertPushedTimes(GenerateTechnicalMemorySection::class, 9);

    Queue::assertPushed(GenerateTechnicalMemorySection::class, function (GenerateTechnicalMemorySection $job): bool {
        return in_array($job->section, [
            'introduction',
            'company_presentation',
            'technical_approach',
            'methodology',
            'team_structure',
            'timeline',
            'quality_assurance',
            'risk_management',
            'compliance_matrix',
        ], true)
            && data_get($job->pcaData, 'evaluation_points.0.section_title') === 'Criterio de solvencia'
            && data_get($job->pcaData, 'evaluation_points.0.points.0') === 'Experiencia demostrable en proyectos similares.'
            && data_get($job->pcaData, 'evaluation_points.0.points.1') === 'Equipo con perfiles certificados.';
    });

    $memory = $tender->fresh()->technicalMemory;

    expect($memory)->not->toBeNull();
    expect($memory?->generated_at)->toBeNull();
});
