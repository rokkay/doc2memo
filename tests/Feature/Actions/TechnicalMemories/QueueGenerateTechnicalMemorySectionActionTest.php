<?php

declare(strict_types=1);

use App\Actions\TechnicalMemories\QueueGenerateTechnicalMemorySectionAction;
use App\Ai\Agents\TechnicalMemoryDynamicSectionAgent;
use App\Data\TechnicalMemoryGenerationContextData;
use App\Data\TechnicalMemorySectionData;
use Laravel\Ai\QueuedAgentPrompt;

it('queues dynamic section generation through agent native queueing', function (): void {
    TechnicalMemoryDynamicSectionAgent::fake()->preventStrayPrompts();

    $section = TechnicalMemorySectionData::fromArray([
        'group_key' => '1.1-metodologia',
        'section_number' => '1.1',
        'section_title' => 'Metodología',
        'total_points' => 16,
        'criteria_count' => 1,
        'criteria' => [],
        'sort_key' => '0001|metodologia',
    ]);

    $context = TechnicalMemoryGenerationContextData::fromArray([
        'pca' => ['criteria' => []],
        'ppt' => ['specifications' => []],
        'memory_title' => 'Memoria test',
        'run_id' => 'run-123',
    ]);

    resolve(QueueGenerateTechnicalMemorySectionAction::class)(
        technicalMemorySectionId: 123,
        section: $section,
        context: $context,
        qualityAttempt: 0,
        runId: 'run-123',
    );

    TechnicalMemoryDynamicSectionAgent::assertQueued(function (QueuedAgentPrompt $prompt): bool {
        return $prompt->agent instanceof TechnicalMemoryDynamicSectionAgent
            && str_contains($prompt->prompt, 'SECCION OBJETIVO');
    });
});
