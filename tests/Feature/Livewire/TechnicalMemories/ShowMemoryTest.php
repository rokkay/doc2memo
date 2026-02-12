<?php

use App\Livewire\TechnicalMemories\ShowMemory;
use App\Models\TechnicalMemory;
use App\Models\Tender;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses()->group('livewire');
uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->seed(\Database\Seeders\DatabaseSeeder::class);
});

it('renders successfully with memory', function (): void {
    $tender = Tender::factory()->create();
    TechnicalMemory::factory()->create([
        'tender_id' => $tender->id,
    ]);

    Livewire::test(ShowMemory::class, ['tender' => $tender])
        ->assertOk();
});

it('displays all sections when present', function (): void {
    $tender = Tender::factory()->create();
    TechnicalMemory::factory()->create([
        'tender_id' => $tender->id,
        'introduction' => 'Texto de introduccion',
        'company_presentation' => 'Texto de presentacion',
        'technical_approach' => 'Texto de enfoque tecnico',
        'methodology' => 'Texto de metodologia',
        'team_structure' => 'Texto de estructura de equipo',
        'timeline' => 'Texto de cronograma',
        'quality_assurance' => 'Texto de calidad',
        'risk_management' => 'Texto de riesgos',
        'compliance_matrix' => 'Texto de cumplimiento',
    ]);

    Livewire::test(ShowMemory::class, ['tender' => $tender])
        ->assertSee('1. Introduccion')
        ->assertSee('2. Presentacion de la Empresa')
        ->assertSee('3. Enfoque Tecnico')
        ->assertSee('4. Metodologia')
        ->assertSee('5. Estructura del Equipo')
        ->assertSee('6. Cronograma')
        ->assertSee('7. Aseguramiento de Calidad')
        ->assertSee('8. Gestion de Riesgos')
        ->assertSee('9. Matriz de Cumplimiento')
        ->assertSee('Texto de introduccion')
        ->assertSee('Texto de presentacion')
        ->assertSee('Texto de enfoque tecnico')
        ->assertSee('Texto de metodologia')
        ->assertSee('Texto de estructura de equipo')
        ->assertSee('Texto de cronograma')
        ->assertSee('Texto de calidad')
        ->assertSee('Texto de riesgos')
        ->assertSee('Texto de cumplimiento');
});

it('displays generation date', function (): void {
    $tender = Tender::factory()->create();
    TechnicalMemory::factory()->create([
        'tender_id' => $tender->id,
        'generated_at' => '2026-01-15 14:30:00',
    ]);

    Livewire::test(ShowMemory::class, ['tender' => $tender])
        ->assertSee('Generada el 15/01/2026 14:30');
});

it('has back link', function (): void {
    $tender = Tender::factory()->create();
    TechnicalMemory::factory()->create([
        'tender_id' => $tender->id,
    ]);

    Livewire::test(ShowMemory::class, ['tender' => $tender])
        ->assertSee('Volver')
        ->assertSee(route('tenders.show', $tender));
});

it('shows download button when file path exists', function (): void {
    $tender = Tender::factory()->create();
    $memory = TechnicalMemory::factory()->create([
        'tender_id' => $tender->id,
        'generated_file_path' => 'technical-memories/memoria.pdf',
    ]);

    Livewire::test(ShowMemory::class, ['tender' => $tender])
        ->assertSee('Descargar PDF')
        ->assertSee(route('technical-memories.download', $memory));
});

it('hides download button when no file path', function (): void {
    $tender = Tender::factory()->create();
    TechnicalMemory::factory()->create([
        'tender_id' => $tender->id,
        'generated_file_path' => null,
    ]);

    Livewire::test(ShowMemory::class, ['tender' => $tender])
        ->assertDontSee('Descargar PDF');
});

it('shows empty state when no memory exists', function (): void {
    $tender = Tender::factory()->create();

    Livewire::test(ShowMemory::class, ['tender' => $tender])
        ->assertSee('No hay memoria tecnica generada')
        ->assertSee('La memoria tecnica aun no ha sido generada para esta licitacion.')
        ->assertSee('Volver a la licitacion')
        ->assertSee(route('tenders.show', $tender));
});

it('polls and shows section progress while memory generation is in draft', function (): void {
    $tender = Tender::factory()->create();

    TechnicalMemory::factory()->create([
        'tender_id' => $tender->id,
        'title' => 'Memoria Tecnica - '.$tender->title,
        'status' => 'draft',
        'generated_at' => null,
        'introduction' => 'Primer bloque completado.',
        'company_presentation' => null,
        'technical_approach' => null,
        'methodology' => null,
        'team_structure' => null,
        'timeline' => null,
        'quality_assurance' => null,
        'risk_management' => null,
        'compliance_matrix' => null,
    ]);

    Livewire::test(ShowMemory::class, ['tender' => $tender])
        ->assertSee('Generando memoria tecnica por secciones')
        ->assertSee('Generacion en curso')
        ->assertSee('1/9')
        ->assertSeeHtml('wire:poll.2s.visible="refreshMemory"');
});

it('renders gantt chart block when timeline has schedulable lines', function (): void {
    $tender = Tender::factory()->create();

    TechnicalMemory::factory()->create([
        'tender_id' => $tender->id,
        'timeline' => 'Cronograma narrativo para entrega por fases.',
        'timeline_plan' => [
            'total_weeks' => 6,
            'tasks' => [
                [
                    'id' => 'analysis',
                    'title' => 'Analisis inicial',
                    'lane' => 'Planificacion',
                    'start_week' => 1,
                    'end_week' => 2,
                    'depends_on' => [],
                ],
                [
                    'id' => 'proposal',
                    'title' => 'Desarrollo de propuesta',
                    'lane' => 'Ejecucion',
                    'start_week' => 3,
                    'end_week' => 5,
                    'depends_on' => ['analysis'],
                ],
            ],
            'milestones' => [
                [
                    'title' => 'Revision final',
                    'week' => 6,
                ],
            ],
        ],
    ]);

    Livewire::test(ShowMemory::class, ['tender' => $tender])
        ->assertSee('Diagrama de cronograma')
        ->assertSee('6 semanas estimadas')
        ->assertSee('Semanas 1-2')
        ->assertSee('Analisis inicial')
        ->assertSee('Depende de: analysis')
        ->assertSee('Semana 6: Revision final');
});
