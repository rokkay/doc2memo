<?php

declare(strict_types=1);

namespace App\Actions\TechnicalMemories;

use App\Actions\TechnicalMemories\Queued\ApplyQueuedTechnicalMemorySectionFailureAction;
use App\Actions\TechnicalMemories\Queued\ApplyQueuedTechnicalMemorySectionResponseAction;
use App\Ai\Agents\TechnicalMemoryDynamicSectionAgent;
use App\Data\TechnicalMemoryGenerationContextData;
use App\Data\TechnicalMemorySectionData;
use Laravel\Ai\Responses\AgentResponse;
use Throwable;

final class QueueGenerateTechnicalMemorySectionAction
{
    public function __invoke(
        int $technicalMemorySectionId,
        TechnicalMemorySectionData $section,
        TechnicalMemoryGenerationContextData $context,
        int $qualityAttempt = 0,
        string $runId = '',
    ): void {
        $agent = new TechnicalMemoryDynamicSectionAgent(
            section: $section->toArray(),
            context: $context->toArray(),
        );

        $sectionPayload = $section->toArray();
        $contextPayload = $context->toArray();

        $agent->queue($agent->buildPromptText())
            ->then(static function (AgentResponse $response) use ($technicalMemorySectionId, $sectionPayload, $contextPayload, $qualityAttempt, $runId): void {
                resolve(ApplyQueuedTechnicalMemorySectionResponseAction::class)(
                    technicalMemorySectionId: $technicalMemorySectionId,
                    sectionPayload: $sectionPayload,
                    contextPayload: $contextPayload,
                    qualityAttempt: $qualityAttempt,
                    runId: $runId,
                    response: $response,
                );
            })
            ->catch(static function (Throwable $exception) use ($technicalMemorySectionId, $sectionPayload, $contextPayload, $qualityAttempt, $runId): void {
                resolve(ApplyQueuedTechnicalMemorySectionFailureAction::class)(
                    technicalMemorySectionId: $technicalMemorySectionId,
                    sectionPayload: $sectionPayload,
                    contextPayload: $contextPayload,
                    qualityAttempt: $qualityAttempt,
                    runId: $runId,
                    exception: $exception,
                );
            });
    }
}
