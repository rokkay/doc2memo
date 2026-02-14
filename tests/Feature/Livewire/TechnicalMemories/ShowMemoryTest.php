<?php

use App\Enums\TechnicalMemorySectionStatus;
use App\Jobs\GenerateTechnicalMemorySection;
use App\Livewire\TechnicalMemories\ShowMemory;
use App\Models\ExtractedCriterion;
use App\Models\TechnicalMemory;
use App\Models\TechnicalMemorySection;
use App\Models\Tender;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Livewire\Livewire;

uses()->group('livewire');
uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->seed(\Database\Seeders\DatabaseSeeder::class);
});

it('renders successfully with dynamic memory sections', function (): void {
    $tender = Tender::factory()->create();
    $memory = TechnicalMemory::factory()->create([
        'tender_id' => $tender->id,
    ]);

    TechnicalMemorySection::factory()->create([
        'technical_memory_id' => $memory->id,
        'group_key' => '1.1-metodologia',
        'section_number' => '1.1',
        'section_title' => 'Metodología',
        'sort_order' => 1,
        'status' => 'completed',
        'content' => 'Texto de metodología.',
    ]);

    ExtractedCriterion::factory()->create([
        'tender_id' => $tender->id,
        'group_key' => '1.1-metodologia',
        'section_number' => '1.1',
        'section_title' => 'Criterios adjudicación (B) Juicio de valor - Metodología',
        'criterion_type' => 'judgment',
        'priority' => 'mandatory',
        'score_points' => 20,
        'source_reference' => 'Tabla B.1',
    ]);

    Livewire::test(ShowMemory::class, ['tender' => $tender])
        ->assertOk()
        ->assertSee('Memoria Técnica Dinámica')
        ->assertSee('1.1 Metodología')
        ->assertSee('Evidencias de evaluación usadas')
        ->assertSee('Origen: Tabla B.1')
        ->assertDontSee('Criterios adjudicación (B) Juicio de valor - Metodología')
        ->assertSee('Texto de metodología.');
});

it('renders section content as markdown html', function (): void {
    $tender = Tender::factory()->create();
    $memory = TechnicalMemory::factory()->create([
        'tender_id' => $tender->id,
    ]);

    TechnicalMemorySection::factory()->create([
        'technical_memory_id' => $memory->id,
        'section_number' => '2.2',
        'section_title' => 'Enfoque',
        'sort_order' => 1,
        'status' => 'completed',
        'content' => "Este bloque usa **negrita** y una lista:\n\n- Punto A\n- Punto B",
    ]);

    Livewire::test(ShowMemory::class, ['tender' => $tender])
        ->assertSeeHtml('<strong>negrita</strong>')
        ->assertSeeHtml('<li>Punto A</li>')
        ->assertSeeHtml('<li>Punto B</li>');
});

it('polls and shows dynamic progress while generation is in draft', function (): void {
    $tender = Tender::factory()->create();
    $memory = TechnicalMemory::factory()->create([
        'tender_id' => $tender->id,
        'status' => 'draft',
        'generated_at' => null,
    ]);

    TechnicalMemorySection::factory()->create([
        'technical_memory_id' => $memory->id,
        'status' => 'completed',
        'section_title' => 'Resumen ejecutivo',
        'sort_order' => 1,
    ]);

    TechnicalMemorySection::factory()->create([
        'technical_memory_id' => $memory->id,
        'status' => 'generating',
        'section_title' => 'Metodologia operativa',
        'sort_order' => 2,
    ]);

    TechnicalMemorySection::factory()->create([
        'technical_memory_id' => $memory->id,
        'status' => 'pending',
        'section_title' => 'Plan de transicion',
        'sort_order' => 3,
    ]);

    Livewire::test(ShowMemory::class, ['tender' => $tender])
        ->assertSee('Generando memoria técnica por secciones dinámicas de juicio de valor.')
        ->assertSee('En cola')
        ->assertSee('En curso')
        ->assertSee('Ahora en curso')
        ->assertSee('Plan de transicion')
        ->assertSee('Metodologia operativa')
        ->assertSee('1/3')
        ->assertSeeHtml('wire:poll.2s.visible="refreshMemory"');
});

it('shows clear per-section state labels for queued and generating sections', function (): void {
    $tender = Tender::factory()->create();
    $memory = TechnicalMemory::factory()->create([
        'tender_id' => $tender->id,
        'status' => 'draft',
        'generated_at' => null,
    ]);

    TechnicalMemorySection::factory()->create([
        'technical_memory_id' => $memory->id,
        'section_title' => 'Seccion en cola',
        'status' => 'pending',
        'content' => null,
        'sort_order' => 1,
    ]);

    TechnicalMemorySection::factory()->create([
        'technical_memory_id' => $memory->id,
        'section_title' => 'Seccion en curso',
        'status' => 'generating',
        'content' => null,
        'sort_order' => 2,
    ]);

    Livewire::test(ShowMemory::class, ['tender' => $tender])
        ->assertSee('Sección en cola, pendiente de pasar a generación.')
        ->assertSee('La IA está redactando esta sección ahora mismo.')
        ->assertSee('Seccion en cola')
        ->assertSee('Seccion en curso');
});

it('shows internal operational metrics for the latest run', function (): void {
    $tender = Tender::factory()->create();
    $memory = TechnicalMemory::factory()->create([
        'tender_id' => $tender->id,
    ]);

    $sectionOne = TechnicalMemorySection::factory()->create([
        'technical_memory_id' => $memory->id,
        'status' => 'completed',
    ]);

    $sectionTwo = TechnicalMemorySection::factory()->create([
        'technical_memory_id' => $memory->id,
        'status' => 'completed',
    ]);

    $memory->metricRuns()->create([
        'run_id' => 'run-latest-1',
        'trigger' => 'full_generation',
        'status' => 'completed',
        'sections_total' => 2,
        'sections_completed' => 2,
        'sections_failed' => 0,
        'sections_retried' => 1,
        'duration_ms' => 4200,
    ]);

    $memory->metricEvents()->create([
        'technical_memory_section_id' => $sectionOne->id,
        'run_id' => 'run-latest-1',
        'attempt' => 1,
        'event_type' => 'completed',
        'duration_ms' => 2000,
        'quality_passed' => true,
        'quality_reasons' => [],
    ]);

    $memory->metricEvents()->create([
        'technical_memory_section_id' => $sectionTwo->id,
        'run_id' => 'run-latest-1',
        'attempt' => 2,
        'event_type' => 'completed',
        'duration_ms' => 3000,
        'quality_passed' => true,
        'quality_reasons' => [],
    ]);

    Livewire::test(ShowMemory::class, ['tender' => $tender])
        ->assertSee('Métricas operativas internas')
        ->assertSee('completed')
        ->assertSee('4200 ms')
        ->assertSee('2500 ms')
        ->assertSee('50,0%')
        ->assertSee('50,0%')
        ->assertSee('0,0%');
});

it('shows section points integrated in the dynamic index', function (): void {
    $tender = Tender::factory()->create();
    $memory = TechnicalMemory::factory()->create([
        'tender_id' => $tender->id,
    ]);

    TechnicalMemorySection::factory()->create([
        'technical_memory_id' => $memory->id,
        'section_title' => 'Metodología',
        'total_points' => 22,
        'sort_order' => 1,
        'status' => 'completed',
    ]);

    TechnicalMemorySection::factory()->create([
        'technical_memory_id' => $memory->id,
        'section_title' => 'Gobierno del servicio',
        'total_points' => 8,
        'sort_order' => 2,
        'status' => 'completed',
    ]);

    Livewire::test(ShowMemory::class, ['tender' => $tender])
        ->assertSee('Índice dinámico')
        ->assertSee('Metodología')
        ->assertSee('22,00 pts')
        ->assertSee('Gobierno del servicio')
        ->assertSee('8,00 pts')
        ->assertDontSee('Matriz de juicio de valor');
});

it('shows empty state when no memory exists', function (): void {
    $tender = Tender::factory()->create();

    Livewire::test(ShowMemory::class, ['tender' => $tender])
        ->assertSee('No hay memoria técnica generada')
        ->assertSee(route('tenders.show', $tender));
});

it('can regenerate a single section from the memory view', function (): void {
    Queue::fake();

    $tender = Tender::factory()->create();
    $memory = TechnicalMemory::factory()->create([
        'tender_id' => $tender->id,
        'status' => 'generated',
        'generated_at' => now(),
    ]);

    $section = TechnicalMemorySection::factory()->create([
        'technical_memory_id' => $memory->id,
        'section_number' => '1.1',
        'section_title' => 'Metodología',
        'group_key' => '1.1-metodologia',
        'status' => 'completed',
        'content' => 'Contenido previo',
    ]);

    ExtractedCriterion::factory()->create([
        'tender_id' => $tender->id,
        'section_number' => '1.1',
        'section_title' => 'Metodología',
        'group_key' => '1.1-metodologia',
        'criterion_type' => 'judgment',
        'priority' => 'mandatory',
        'score_points' => 16,
    ]);

    Livewire::test(ShowMemory::class, ['tender' => $tender])
        ->call('regenerateSection', $section->id);

    $section = $section->fresh();
    $memory = $memory->fresh();

    expect($section?->status)->toBe(TechnicalMemorySectionStatus::Pending);
    expect($section?->content)->toBeNull();
    expect($memory?->status)->toBe('draft');
    expect($memory?->generated_at)->toBeNull();

    Queue::assertPushed(GenerateTechnicalMemorySection::class, function (GenerateTechnicalMemorySection $job) use ($section): bool {
        return $job->technicalMemorySectionId === $section?->id;
    });
});
