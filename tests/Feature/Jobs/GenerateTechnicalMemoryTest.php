<?php

declare(strict_types=1);

use App\Ai\Agents\TechnicalMemoryGenerator;
use App\Jobs\GenerateTechnicalMemory;
use App\Models\Document;
use App\Models\ExtractedCriterion;
use App\Models\ExtractedSpecification;
use App\Models\Tender;
use Illuminate\Foundation\Testing\RefreshDatabase;

use function Pest\Laravel\assertDatabaseHas;

uses(RefreshDatabase::class);

it('generates a technical memory from extracted data', function (): void {
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
        'description' => 'Acreditar experiencia en proyectos similares.',
        'priority' => 'mandatory',
    ]);

    ExtractedSpecification::factory()->create([
        'tender_id' => $tender->id,
        'document_id' => $pptDocument->id,
        'section_title' => 'Arquitectura',
        'technical_description' => 'Migracion a Drupal actualizado con enfoque accesible.',
        'requirements' => 'Cumplir WCAG 2.1 AA',
    ]);

    TechnicalMemoryGenerator::fake([
        [
            'title' => 'Memoria tecnica para '.$tender->title,
            'introduction' => 'Introduccion generada por IA.',
            'company_presentation' => 'Presentacion de la empresa.',
            'technical_approach' => 'Enfoque tecnico detallado.',
            'methodology' => 'Metodologia de trabajo iterativa.',
            'team_structure' => 'Equipo con jefe de proyecto y analistas.',
            'timeline' => 'Plan de 24 meses con hitos trimestrales.',
            'quality_assurance' => 'Plan de calidad y pruebas.',
            'risk_management' => 'Matriz de riesgos y mitigaciones.',
            'compliance_matrix' => 'Tabla de cumplimiento criterio-solucion.',
            'full_report_markdown' => '# Memoria tecnica\n\nContenido completo.',
        ],
    ])->preventStrayPrompts();

    (new GenerateTechnicalMemory($tender))->handle();

    assertDatabaseHas('technical_memories', [
        'tender_id' => $tender->id,
        'status' => 'generated',
        'title' => 'Memoria tecnica para '.$tender->title,
    ]);
});
