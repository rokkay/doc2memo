<?php

declare(strict_types=1);

namespace App\Actions\TechnicalMemories;

use App\Models\TechnicalMemory;
use App\Models\TechnicalMemoryMetricEvent;
use App\Models\TechnicalMemoryMetricRun;
use App\Support\TechnicalMemoryMetrics;

final class UpsertMetricRunSummaryAction
{
    public function __invoke(
        TechnicalMemory $memory,
        string $runId,
        ?string $trigger = null,
        ?int $sectionsTotal = null,
    ): TechnicalMemoryMetricRun
    {
        $existingRun = TechnicalMemoryMetricRun::query()
            ->where('technical_memory_id', $memory->id)
            ->where('run_id', $runId)
            ->first();

        $resolvedTrigger = $trigger ?? $existingRun?->trigger ?? 'full_generation';
        $resolvedSectionsTotal = $sectionsTotal ?? $existingRun?->sections_total ?? 1;

        $run = TechnicalMemoryMetricRun::query()->firstOrCreate(
            [
                'technical_memory_id' => $memory->id,
                'run_id' => $runId,
            ],
            [
                'trigger' => $resolvedTrigger,
                'status' => 'running',
                'sections_total' => $resolvedSectionsTotal,
                'sections_completed' => 0,
                'sections_failed' => 0,
                'sections_retried' => 0,
                'duration_ms' => null,
            ],
        );

        if ($run->trigger !== $resolvedTrigger || $run->sections_total !== $resolvedSectionsTotal) {
            $run->fill([
                'trigger' => $resolvedTrigger,
                'sections_total' => $resolvedSectionsTotal,
            ])->save();
        }

        $events = TechnicalMemoryMetricEvent::query()
            ->where('technical_memory_id', $memory->id)
            ->where('run_id', $runId)
            ->orderBy('id')
            ->get();

        $sectionsCompleted = 0;
        $sectionsFailed = 0;

        $terminalEventsBySection = $events
            ->filter(fn (TechnicalMemoryMetricEvent $event): bool => $event->technical_memory_section_id !== null)
            ->groupBy('technical_memory_section_id')
            ->map(fn ($sectionEvents) => $sectionEvents
                ->whereIn('event_type', [TechnicalMemoryMetrics::EVENT_COMPLETED, TechnicalMemoryMetrics::EVENT_FAILED])
                ->last())
            ->filter();

        foreach ($terminalEventsBySection as $terminalEvent) {
            if ($terminalEvent->event_type === TechnicalMemoryMetrics::EVENT_COMPLETED) {
                $sectionsCompleted++;
            }

            if ($terminalEvent->event_type === TechnicalMemoryMetrics::EVENT_FAILED) {
                $sectionsFailed++;
            }
        }

        $sectionsRetried = $events
            ->where('event_type', TechnicalMemoryMetrics::EVENT_REQUEUED)
            ->pluck('technical_memory_section_id')
            ->filter()
            ->unique()
            ->count();

        $durationMs = null;

        if ($events->isNotEmpty()) {
            $firstEventTime = $events->first()->created_at;
            $lastEventTime = $events->last()->created_at;

            if ($firstEventTime !== null && $lastEventTime !== null) {
                $durationMs = $firstEventTime->diffInMilliseconds($lastEventTime);
            }
        }

        $isRunComplete = $resolvedSectionsTotal === 0
            || ($sectionsCompleted + $sectionsFailed) >= $resolvedSectionsTotal;

        $run->fill([
            'status' => $isRunComplete ? 'completed' : 'running',
            'sections_completed' => $sectionsCompleted,
            'sections_failed' => $sectionsFailed,
            'sections_retried' => $sectionsRetried,
            'duration_ms' => $durationMs,
        ])->save();

        return $run;
    }
}
