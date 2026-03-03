<?php

declare(strict_types=1);

namespace App\Actions\TechnicalMemories\Queued;

use App\Data\TechnicalMemoryGenerationContextData;
use App\Data\TechnicalMemorySectionData;
use App\Jobs\GenerateTechnicalMemorySection;
use Throwable;

final class ApplyQueuedTechnicalMemorySectionFailureAction
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
        Throwable $exception,
    ): void {
        $job = new GenerateTechnicalMemorySection(
            technicalMemorySectionId: $technicalMemorySectionId,
            section: TechnicalMemorySectionData::fromArray($sectionPayload),
            context: TechnicalMemoryGenerationContextData::fromArray($contextPayload),
            qualityAttempt: $qualityAttempt,
            runId: $runId,
            forcedDynamicFailureMessage: $exception->getMessage(),
            useNativeQueueing: true,
        );

        try {
            $job->handle();
        } catch (Throwable) {
            return;
        }
    }
}
