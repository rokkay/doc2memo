<?php

namespace App\Jobs;

use App\Actions\TechnicalMemories\RecordMetricEventAction;
use App\Ai\Agents\TechnicalMemoryDynamicSectionAgent;
use App\Ai\Agents\TechnicalMemorySectionEditorAgent;
use App\Data\TechnicalMemoryGenerationContextData;
use App\Data\TechnicalMemorySectionData;
use App\Enums\TechnicalMemorySectionStatus;
use App\Models\TechnicalMemorySection;
use App\Support\TechnicalMemoryMetrics;
use App\Support\TechnicalMemorySectionQualityGate;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Throwable;

class GenerateTechnicalMemorySection implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public int $technicalMemorySectionId,
        public TechnicalMemorySectionData $section,
        public TechnicalMemoryGenerationContextData $context,
        public int $qualityAttempt = 0,
    ) {}

    public function handle(): void
    {
        $startedAt = microtime(true);

        $section = TechnicalMemorySection::query()
            ->with('technicalMemory')
            ->find($this->technicalMemorySectionId);

        if (! $section || ! $section->technicalMemory) {
            return;
        }

        $context = $this->context->runId !== null && $this->context->runId !== ''
            ? $this->context
            : $this->context->withRunId((string) Str::uuid());

        $memory = $section->technicalMemory;
        $recordMetricEvent = new RecordMetricEventAction;
        $attempt = $this->qualityAttempt + 1;
        $usedStyleEditor = (bool) config('technical_memory.style_editor.enabled', true);
        $outputChars = null;
        $outputH3Count = null;

        $recordMetricEvent(
            memory: $memory,
            section: $section,
            runId: (string) $context->runId,
            attempt: $attempt,
            eventType: TechnicalMemoryMetrics::EVENT_STARTED,
            status: TechnicalMemorySectionStatus::Generating->value,
            durationMs: 0,
            usedStyleEditor: $usedStyleEditor,
            metadata: [
                'quality_attempt' => $this->qualityAttempt,
            ],
        );

        $section->update([
            'status' => TechnicalMemorySectionStatus::Generating,
            'error_message' => null,
        ]);

        try {
            $qualityGate = new TechnicalMemorySectionQualityGate;

            $content = (new TechnicalMemoryDynamicSectionAgent(
                section: $this->section->toArray(),
                context: $context->toArray(),
            ))->generate();

            if ($usedStyleEditor) {
                try {
                    $editedContent = (new TechnicalMemorySectionEditorAgent(
                        section: $this->section->toArray(),
                    ))->edit($content);

                    if ($editedContent !== '') {
                        $content = $editedContent;
                    }
                } catch (Throwable $exception) {
                    Log::warning('Section style editor failed, using raw generated content.', [
                        'technical_memory_section_id' => $section->id,
                        'error' => $exception->getMessage(),
                    ]);
                }
            }

            $medianCompletedLength = $this->medianCompletedSectionLength($memory->id, $section->id);
            $qualityCheck = $qualityGate->evaluate($content, $medianCompletedLength);
            $outputChars = mb_strlen(trim($content));
            $outputH3Count = preg_match_all('/^###\s+/m', $content) ?: 0;
            $durationMs = (int) round((microtime(true) - $startedAt) * 1000);

            Log::info('Technical memory section generation quality evaluation completed.', [
                'technical_memory_id' => $memory->id,
                'technical_memory_section_id' => $section->id,
                'quality_attempt' => $this->qualityAttempt,
                'passes_quality_gate' => $qualityCheck['passes'],
                'quality_reasons' => $qualityCheck['reasons'],
                'content_length' => $outputChars,
                'duration_ms' => $durationMs,
            ]);

            if (! $qualityCheck['passes']) {
                $qualityFeedback = implode(' ', $qualityCheck['reasons']);
                $recordMetricEvent(
                    memory: $memory,
                    section: $section,
                    runId: (string) $context->runId,
                    attempt: $attempt,
                    eventType: TechnicalMemoryMetrics::EVENT_QUALITY_FAILED,
                    status: TechnicalMemorySectionStatus::Generating->value,
                    durationMs: $durationMs,
                    qualityPassed: false,
                    qualityReasons: $qualityCheck['reasons'],
                    outputChars: $outputChars,
                    outputH3Count: $outputH3Count,
                    usedStyleEditor: $usedStyleEditor,
                    metadata: [
                        'quality_attempt' => $this->qualityAttempt,
                        'max_retry_attempts' => (int) config('technical_memory.quality_gate.max_retry_attempts', 1),
                    ],
                );

                $maxRetryAttempts = (int) config('technical_memory.quality_gate.max_retry_attempts', 1);

                if ($this->qualityAttempt < $maxRetryAttempts) {
                    $section->update([
                        'status' => TechnicalMemorySectionStatus::Pending,
                        'error_message' => $qualityFeedback,
                    ]);

                    self::dispatch(
                        technicalMemorySectionId: $this->technicalMemorySectionId,
                        section: $this->section,
                        context: $context->withQualityFeedback($qualityFeedback),
                        qualityAttempt: $this->qualityAttempt + 1,
                    );

                    $recordMetricEvent(
                        memory: $memory,
                        section: $section,
                        runId: (string) $context->runId,
                        attempt: $attempt,
                        eventType: TechnicalMemoryMetrics::EVENT_REQUEUED,
                        status: TechnicalMemorySectionStatus::Pending->value,
                        durationMs: $durationMs,
                        qualityPassed: false,
                        qualityReasons: $qualityCheck['reasons'],
                        outputChars: $outputChars,
                        outputH3Count: $outputH3Count,
                        usedStyleEditor: $usedStyleEditor,
                        metadata: [
                            'quality_attempt' => $this->qualityAttempt,
                            'next_quality_attempt' => $this->qualityAttempt + 1,
                        ],
                    );

                    return;
                }

                $section->update([
                    'status' => TechnicalMemorySectionStatus::Failed,
                    'error_message' => $qualityFeedback,
                ]);

                $recordMetricEvent(
                    memory: $memory,
                    section: $section,
                    runId: (string) $context->runId,
                    attempt: $attempt,
                    eventType: TechnicalMemoryMetrics::EVENT_FAILED,
                    status: TechnicalMemorySectionStatus::Failed->value,
                    durationMs: $durationMs,
                    qualityPassed: false,
                    qualityReasons: $qualityCheck['reasons'],
                    outputChars: $outputChars,
                    outputH3Count: $outputH3Count,
                    usedStyleEditor: $usedStyleEditor,
                    metadata: [
                        'quality_attempt' => $this->qualityAttempt,
                        'reason' => 'quality_gate_exhausted',
                    ],
                );

                return;
            }

            $section->update([
                'status' => TechnicalMemorySectionStatus::Completed,
                'content' => $content,
                'error_message' => null,
            ]);

            $recordMetricEvent(
                memory: $memory,
                section: $section,
                runId: (string) $context->runId,
                attempt: $attempt,
                eventType: TechnicalMemoryMetrics::EVENT_COMPLETED,
                status: TechnicalMemorySectionStatus::Completed->value,
                durationMs: $durationMs,
                qualityPassed: true,
                qualityReasons: [],
                outputChars: $outputChars,
                outputH3Count: $outputH3Count,
                usedStyleEditor: $usedStyleEditor,
                metadata: [
                    'quality_attempt' => $this->qualityAttempt,
                ],
            );

            Log::info('Technical memory section generation completed.', [
                'technical_memory_id' => $memory->id,
                'technical_memory_section_id' => $section->id,
                'quality_attempt' => $this->qualityAttempt,
                'duration_ms' => $durationMs,
            ]);
        } catch (Throwable $exception) {
            $durationMs = (int) round((microtime(true) - $startedAt) * 1000);

            $section->update([
                'status' => TechnicalMemorySectionStatus::Failed,
                'error_message' => $exception->getMessage(),
            ]);

            $recordMetricEvent(
                memory: $memory,
                section: $section,
                runId: (string) $context->runId,
                attempt: $attempt,
                eventType: TechnicalMemoryMetrics::EVENT_FAILED,
                status: TechnicalMemorySectionStatus::Failed->value,
                durationMs: $durationMs,
                outputChars: $outputChars,
                outputH3Count: $outputH3Count,
                usedStyleEditor: $usedStyleEditor,
                metadata: [
                    'quality_attempt' => $this->qualityAttempt,
                    'error' => $exception->getMessage(),
                    'exception_class' => $exception::class,
                ],
            );

            Log::error('Technical memory section generation failed.', [
                'technical_memory_id' => $memory->id,
                'technical_memory_section_id' => $section->id,
                'quality_attempt' => $this->qualityAttempt,
                'duration_ms' => $durationMs,
                'error' => $exception->getMessage(),
            ]);

            throw $exception;
        }

        $memory = $memory->fresh();

        if (! $memory || $memory->status !== 'draft') {
            return;
        }

        $pendingSections = $memory->sections()
            ->whereIn('status', TechnicalMemorySectionStatus::blockingValues())
            ->exists();

        if (! $pendingSections) {
            $memory->update([
                'status' => 'generated',
                'generated_at' => now(),
            ]);
        }
    }

    private function medianCompletedSectionLength(int $memoryId, int $excludeSectionId): ?int
    {
        $lengths = TechnicalMemorySection::query()
            ->where('technical_memory_id', $memoryId)
            ->where('id', '!=', $excludeSectionId)
            ->where('status', TechnicalMemorySectionStatus::Completed->value)
            ->whereNotNull('content')
            ->pluck('content')
            ->map(fn (string $content): int => mb_strlen(trim($content)))
            ->filter(fn (int $length): bool => $length > 0)
            ->sort()
            ->values();

        $count = $lengths->count();

        if ($count === 0) {
            return null;
        }

        $middle = intdiv($count, 2);

        if ($count % 2 === 0) {
            return (int) round(($lengths[$middle - 1] + $lengths[$middle]) / 2);
        }

        return $lengths[$middle];
    }
}
