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
        'section_number' => '1.1',
        'section_title' => 'Metodología',
        'sort_order' => 1,
        'status' => 'completed',
        'content' => 'Texto de metodología.',
    ]);

    ExtractedCriterion::factory()->create([
        'tender_id' => $tender->id,
        'section_number' => '1.1',
        'section_title' => 'Criterios adjudicación (B) Juicio de valor - Metodología',
        'criterion_type' => 'judgment',
        'priority' => 'mandatory',
        'score_points' => 20,
    ]);

    Livewire::test(ShowMemory::class, ['tender' => $tender])
        ->assertOk()
        ->assertSee('Memoria Técnica Dinámica')
        ->assertSee('1.1 Metodología')
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
        'sort_order' => 1,
    ]);

    TechnicalMemorySection::factory()->create([
        'technical_memory_id' => $memory->id,
        'status' => 'pending',
        'sort_order' => 2,
    ]);

    Livewire::test(ShowMemory::class, ['tender' => $tender])
        ->assertSee('Generando memoria técnica por secciones dinámicas de juicio de valor.')
        ->assertSee('1/2')
        ->assertSeeHtml('wire:poll.2s.visible="refreshMemory"');
});

it('shows judgment matrix and supports priority filters', function (): void {
    $tender = Tender::factory()->create();
    $memory = TechnicalMemory::factory()->create([
        'tender_id' => $tender->id,
    ]);

    TechnicalMemorySection::factory()->create([
        'technical_memory_id' => $memory->id,
        'status' => 'completed',
    ]);

    ExtractedCriterion::factory()->create([
        'tender_id' => $tender->id,
        'section_title' => 'Criterio obligatorio UX',
        'priority' => 'mandatory',
        'criterion_type' => 'judgment',
        'score_points' => 22,
    ]);

    ExtractedCriterion::factory()->create([
        'tender_id' => $tender->id,
        'section_title' => 'Criterio opcional UX',
        'priority' => 'optional',
        'criterion_type' => 'judgment',
        'score_points' => 8,
    ]);

    ExtractedCriterion::factory()->create([
        'tender_id' => $tender->id,
        'section_title' => 'Criterio automático precio',
        'priority' => 'mandatory',
        'criterion_type' => 'automatic',
        'score_points' => 40,
    ]);

    Livewire::test(ShowMemory::class, ['tender' => $tender])
        ->assertSee('Matriz de juicio de valor')
        ->assertSee('Mostrando 2 de 2 criterios')
        ->assertSee('Criterio obligatorio UX')
        ->assertSee('Criterio opcional UX')
        ->assertDontSee('Criterio automático precio')
        ->call('setCriteriaPriorityFilter', 'mandatory')
        ->assertSee('Mostrando 1 de 2 criterios')
        ->assertSee('Criterio obligatorio UX')
        ->assertDontSee('Criterio opcional UX');
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
