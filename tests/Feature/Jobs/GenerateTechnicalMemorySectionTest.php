<?php

declare(strict_types=1);

use App\Ai\Agents\TechnicalMemoryComplianceMatrixAgent;
use App\Ai\Agents\TechnicalMemoryIntroductionAgent;
use App\Jobs\GenerateTechnicalMemorySection;
use App\Models\TechnicalMemory;
use App\Models\Tender;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('generates one section and keeps memory in draft when pending sections remain', function (): void {
    $tender = Tender::factory()->create();
    $memory = TechnicalMemory::factory()->create([
        'tender_id' => $tender->id,
        'status' => 'draft',
        'generated_at' => null,
        'introduction' => null,
    ]);

    TechnicalMemoryIntroductionAgent::fake([
        ['introduction' => 'Introduccion redactada por agente dedicado.'],
    ])->preventStrayPrompts();

    (new GenerateTechnicalMemorySection($memory->id, 'introduction', [], []))->handle();

    $memory = $memory->fresh();

    expect($memory)->not->toBeNull();
    expect($memory?->introduction)->toBe('Introduccion redactada por agente dedicado.');
    expect($memory?->status)->toBe('draft');
    expect($memory?->generated_at)->toBeNull();
});

it('marks memory as generated when the last section finishes', function (): void {
    $tender = Tender::factory()->create();

    $memory = TechnicalMemory::factory()->create([
        'tender_id' => $tender->id,
        'title' => 'Memoria Tecnica - '.$tender->title,
        'status' => 'draft',
        'generated_at' => null,
        'introduction' => 'Contenido 1',
        'company_presentation' => 'Contenido 2',
        'technical_approach' => 'Contenido 3',
        'methodology' => 'Contenido 4',
        'team_structure' => 'Contenido 5',
        'timeline' => 'Contenido 6',
        'quality_assurance' => 'Contenido 7',
        'risk_management' => 'Contenido 8',
        'compliance_matrix' => null,
    ]);

    TechnicalMemoryComplianceMatrixAgent::fake([
        ['compliance_matrix' => 'Contenido 9'],
    ])->preventStrayPrompts();

    (new GenerateTechnicalMemorySection($memory->id, 'compliance_matrix', [], []))->handle();

    $memory = $memory->fresh();

    expect($memory)->not->toBeNull();
    expect($memory?->status)->toBe('generated');
    expect($memory?->generated_at)->not->toBeNull();
    expect($memory?->full_report_markdown)->toContain('## Introduccion');
    expect($memory?->full_report_markdown)->toContain('## Matriz de Cumplimiento');
});
