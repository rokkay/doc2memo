<?php

declare(strict_types=1);

use App\Actions\TechnicalMemories\RegenerateSectionAction;
use App\Ai\Agents\TechnicalMemoryDynamicSectionAgent;
use App\Enums\TechnicalMemorySectionStatus;
use App\Models\Document;
use App\Models\DocumentInsight;
use App\Models\ExtractedCriterion;
use App\Models\ExtractedSpecification;
use App\Models\TechnicalMemory;
use App\Models\TechnicalMemorySection;
use App\Models\Tender;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Laravel\Ai\QueuedAgentPrompt;

uses(RefreshDatabase::class);

it('resets and requeues a single section regeneration', function (): void {
    TechnicalMemoryDynamicSectionAgent::fake()->preventStrayPrompts();

    $tender = Tender::factory()->create();
    $memory = TechnicalMemory::factory()->create([
        'tender_id' => $tender->id,
        'status' => 'generated',
        'generated_at' => now(),
    ]);

    $section = TechnicalMemorySection::factory()->create([
        'technical_memory_id' => $memory->id,
        'group_key' => '1.1-metodologia',
        'section_number' => '1.1',
        'section_title' => 'Metodología',
        'status' => TechnicalMemorySectionStatus::Completed,
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

    $existingRunId = (string) Str::uuid();

    $memory->metricRuns()->create([
        'run_id' => $existingRunId,
        'trigger' => 'section_regeneration',
        'status' => 'completed',
        'sections_total' => 1,
        'sections_completed' => 1,
        'sections_failed' => 0,
        'sections_retried' => 0,
        'duration_ms' => 100,
    ]);

    (new RegenerateSectionAction)($memory, $section);

    $section = $section->fresh();
    $memory = $memory->fresh();

    expect($section?->status)->toBe(TechnicalMemorySectionStatus::Pending);
    expect($section?->content)->toBeNull();
    expect($memory?->status)->toBe('draft');
    expect($memory?->generated_at)->toBeNull();
    expect($memory)->not->toBeNull();

    TechnicalMemoryDynamicSectionAgent::assertQueued(function (QueuedAgentPrompt $prompt): bool {
        return $prompt->agent instanceof TechnicalMemoryDynamicSectionAgent
            && $prompt->prompt !== '';
    });

    expect($memory->metricRuns()->latest('id')->value('run_id'))->not->toBe($existingRunId);
});

it('builds regeneration context with analyzer criteria and document insights', function (): void {
    TechnicalMemoryDynamicSectionAgent::fake()->preventStrayPrompts();

    $tender = Tender::factory()->create();
    $pcaDocument = Document::factory()->create([
        'tender_id' => $tender->id,
        'document_type' => 'pca',
    ]);
    $pptDocument = Document::factory()->create([
        'tender_id' => $tender->id,
        'document_type' => 'ppt',
    ]);

    $memory = TechnicalMemory::factory()->create([
        'tender_id' => $tender->id,
    ]);

    $section = TechnicalMemorySection::factory()->create([
        'technical_memory_id' => $memory->id,
        'group_key' => '1.1-metodologia',
        'section_number' => '1.1',
        'section_title' => 'Metodología',
        'status' => TechnicalMemorySectionStatus::Failed,
    ]);

    ExtractedCriterion::factory()->create([
        'tender_id' => $tender->id,
        'document_id' => $pcaDocument->id,
        'group_key' => '1.1-metodologia',
        'criterion_type' => 'judgment',
        'source' => 'analyzer',
        'section_number' => '1.1',
        'section_title' => 'Metodología',
    ]);

    ExtractedSpecification::factory()->create([
        'tender_id' => $tender->id,
        'document_id' => $pptDocument->id,
        'section_title' => 'Arquitectura',
    ]);

    DocumentInsight::factory()->create([
        'tender_id' => $tender->id,
        'document_id' => $pcaDocument->id,
        'importance' => 'high',
        'topic' => 'Plazos',
    ]);

    DocumentInsight::factory()->create([
        'tender_id' => $tender->id,
        'document_id' => $pptDocument->id,
        'importance' => 'medium',
        'topic' => 'Arquitectura',
    ]);

    (new RegenerateSectionAction)($memory, $section);

    TechnicalMemoryDynamicSectionAgent::assertQueued(function (QueuedAgentPrompt $prompt): bool {
        if (! $prompt->agent instanceof TechnicalMemoryDynamicSectionAgent) {
            return false;
        }

        $promptText = $prompt->agent->buildPromptText();

        return str_contains($promptText, 'Plazos')
            && str_contains($promptText, 'Arquitectura')
            && str_contains($promptText, 'Metodología');
    });
});

it('prioritizes dedicated extractor criteria during regeneration', function (): void {
    TechnicalMemoryDynamicSectionAgent::fake()->preventStrayPrompts();

    $tender = Tender::factory()->create();
    $memory = TechnicalMemory::factory()->create([
        'tender_id' => $tender->id,
    ]);

    $section = TechnicalMemorySection::factory()->create([
        'technical_memory_id' => $memory->id,
        'group_key' => '1.1-metodologia',
        'section_number' => '1.1',
        'section_title' => 'Metodología',
    ]);

    ExtractedCriterion::factory()->create([
        'tender_id' => $tender->id,
        'group_key' => '1.1-metodologia',
        'criterion_type' => 'judgment',
        'source' => 'analyzer',
        'section_title' => 'Desde analyzer',
    ]);

    ExtractedCriterion::factory()->create([
        'tender_id' => $tender->id,
        'group_key' => '1.1-metodologia',
        'criterion_type' => 'judgment',
        'source' => 'dedicated_extractor',
        'section_title' => 'Desde dedicated',
    ]);

    (new RegenerateSectionAction)($memory, $section);

    TechnicalMemoryDynamicSectionAgent::assertQueued(function (QueuedAgentPrompt $prompt): bool {
        if (! $prompt->agent instanceof TechnicalMemoryDynamicSectionAgent) {
            return false;
        }

        $promptText = $prompt->agent->buildPromptText();

        return str_contains($promptText, 'Desde dedicated')
            && str_contains($promptText, 'dedicated_extractor')
            && ! str_contains($promptText, 'Desde analyzer');
    });
});
