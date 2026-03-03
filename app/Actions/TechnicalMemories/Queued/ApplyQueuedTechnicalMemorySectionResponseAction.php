<?php

declare(strict_types=1);

namespace App\Actions\TechnicalMemories\Queued;

use App\Actions\TechnicalMemories\GenerateTechnicalMemorySectionAction;
use App\Ai\Agents\TechnicalMemoryDynamicSectionAgent;
use App\Data\TechnicalMemoryGenerationContextData;
use App\Data\TechnicalMemorySectionData;
use Laravel\Ai\Responses\AgentResponse;
use Throwable;

final class ApplyQueuedTechnicalMemorySectionResponseAction
{
    /**
     * @param  array<string,mixed>  $sectionPayload
     * @param  array<string,mixed>  $contextPayload
     */
    public function __invoke(
        int $technicalMemorySectionId,
        array $sectionPayload,
        array $contextPayload,
        int $qualityAttempt,
        string $runId,
        AgentResponse $response,
    ): void {
        $section = TechnicalMemorySectionData::fromArray($sectionPayload);
        $context = TechnicalMemoryGenerationContextData::fromArray($contextPayload);

        $prefetchedContent = (new TechnicalMemoryDynamicSectionAgent(
            section: $section->toArray(),
            context: $context->toArray(),
        ))->extractContent($response);

        $job = new GenerateTechnicalMemorySectionAction(
            technicalMemorySectionId: $technicalMemorySectionId,
            section: $section,
            context: $context,
            qualityAttempt: $qualityAttempt,
            runId: $runId,
            prefetchedDynamicContent: $prefetchedContent,
            useNativeQueueing: true,
        );

        try {
            $job->handle();
        } catch (Throwable) {
            return;
        }
    }
}
