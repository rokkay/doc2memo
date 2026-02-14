<?php

declare(strict_types=1);

namespace App\Actions\TechnicalMemories;

use App\Models\TechnicalMemory;
use App\Models\TechnicalMemoryMetricEvent;
use App\Models\TechnicalMemorySection;

final class RecordMetricEventAction
{
    /**
     * @param  array<int,string>|null  $qualityReasons
     * @param  array<string,mixed>|null  $metadata
     */
    public function __invoke(
        TechnicalMemory $memory,
        TechnicalMemorySection $section,
        string $runId,
        int $attempt,
        string $eventType,
        ?string $status = null,
        ?int $durationMs = null,
        ?bool $qualityPassed = null,
        ?array $qualityReasons = null,
        ?int $outputChars = null,
        ?int $outputH3Count = null,
        bool $usedStyleEditor = true,
        ?array $metadata = null,
    ): TechnicalMemoryMetricEvent {
        return TechnicalMemoryMetricEvent::query()->create([
            'technical_memory_id' => $memory->id,
            'technical_memory_section_id' => $section->id,
            'run_id' => $runId,
            'attempt' => $attempt,
            'event_type' => $eventType,
            'status' => $status,
            'duration_ms' => $durationMs,
            'quality_passed' => $qualityPassed,
            'quality_reasons' => $qualityReasons,
            'output_chars' => $outputChars,
            'output_h3_count' => $outputH3Count,
            'used_style_editor' => $usedStyleEditor,
            'metadata' => $metadata,
        ]);
    }
}
