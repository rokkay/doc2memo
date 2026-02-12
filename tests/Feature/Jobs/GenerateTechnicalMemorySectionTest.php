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
        ['introduction' => "### Contexto\n\nEvoluci\x13n redactada por agente dedicado.\n\n- Alcance\n- Riesgos"],
    ])->preventStrayPrompts();

    (new GenerateTechnicalMemorySection($memory->id, 'introduction', [], []))->handle();

    $memory = $memory->fresh();

    expect($memory)->not->toBeNull();
    expect($memory?->introduction)->toContain('### Contexto');
    expect($memory?->introduction)->toContain('Evolución redactada por agente dedicado.');
    expect($memory?->introduction)->not->toContain("\x13");
    expect($memory?->introduction)->toContain('- Alcance');
    expect($memory?->status)->toBe('draft');
    expect($memory?->generated_at)->toBeNull();
});

it('marks memory as generated when the last section finishes', function (): void {
    $tender = Tender::factory()->create();

    $memory = TechnicalMemory::factory()->create([
        'tender_id' => $tender->id,
        'title' => 'Memoria Técnica - '.$tender->title,
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
        ['compliance_matrix' => "### Cobertura de criterios\n\n| Criterio | Estrategia |\n| --- | --- |\n| C1 | Evidencia documental |"],
    ])->preventStrayPrompts();

    (new GenerateTechnicalMemorySection($memory->id, 'compliance_matrix', [], []))->handle();

    $memory = $memory->fresh();

    expect($memory)->not->toBeNull();
    expect($memory?->status)->toBe('generated');
    expect($memory?->generated_at)->not->toBeNull();
    expect($memory?->introduction)->toBe('Contenido 1');
    expect($memory?->compliance_matrix)->toContain('### Cobertura de criterios');
    expect($memory?->compliance_matrix)->toContain('| Criterio | Estrategia |');
});
