<?php

declare(strict_types=1);

namespace App\ViewData;

use App\Enums\TechnicalMemorySectionStatus;
use App\Models\Tender;
use App\Support\SectionTitleNormalizer;
use App\Support\TechnicalMemoryMarkdownBuilder;
use App\Support\TechnicalMemoryMetrics;

final class TechnicalMemoryViewData
{
    /**
     * @param  array<int,array{id:int,anchor:string,title:string,content:string,points:float,weight:float,criteria_count:int,status:string,evidence:array<int,array{label:string,detail:string,reference:?string}>}>  $sections
     * @param  array<int,array{id:int,anchor:string,title:string,content:string,points:float,weight:float,criteria_count:int,status:string,evidence:array<int,array{label:string,detail:string,reference:?string}>}>  $inProgressSections
     * @param  array<int,array{id:int,anchor:string,title:string,content:string,points:float,weight:float,criteria_count:int,status:string,evidence:array<int,array{label:string,detail:string,reference:?string}>}>  $pendingSections
     * @param  array<int,array{id:int,anchor:string,title:string,content:string,points:float,weight:float,criteria_count:int,status:string,evidence:array<int,array{label:string,detail:string,reference:?string}>}>  $generatingSections
     * @param  array<int,array{id:int,anchor:string,title:string,content:string,points:float,weight:float,criteria_count:int,status:string,evidence:array<int,array{label:string,detail:string,reference:?string}>}>  $failedSections
     */
    public function __construct(
        public readonly bool $hasMemory,
        public readonly bool $isGenerating,
        public readonly array $sections,
        public readonly array $inProgressSections,
        public readonly array $pendingSections,
        public readonly array $generatingSections,
        public readonly array $failedSections,
        public readonly int $pendingCount,
        public readonly int $generatingCount,
        public readonly int $failedCount,
        public readonly int $completedCount,
        public readonly int $totalCount,
        public readonly float $totalPoints,
        public readonly int $progressPercent,
        public readonly string $markdownExport,
        public readonly ?string $latestRunStatus,
        public readonly ?int $latestRunDurationMs,
        public readonly ?int $avgSectionDurationMs,
        public readonly float $firstPassRate,
        public readonly float $retryRate,
        public readonly float $failureRate,
    ) {}

    public static function fromTender(Tender $tender): self
    {
        $memory = $tender->technicalMemory;

        if (! $memory) {
            return new self(
                hasMemory: false,
                isGenerating: false,
                sections: [],
                inProgressSections: [],
                pendingSections: [],
                generatingSections: [],
                failedSections: [],
                pendingCount: 0,
                generatingCount: 0,
                failedCount: 0,
                completedCount: 0,
                totalCount: 0,
                totalPoints: 0.0,
                progressPercent: 0,
                markdownExport: '',
                latestRunStatus: null,
                latestRunDurationMs: null,
                avgSectionDurationMs: null,
                firstPassRate: 0.0,
                retryRate: 0.0,
                failureRate: 0.0,
            );
        }

        $latestRun = $memory->metricRuns()->latest('created_at')->latest('id')->first();

        $latestRunStatus = null;
        $latestRunDurationMs = null;
        $avgSectionDurationMs = null;
        $firstPassRate = 0.0;
        $retryRate = 0.0;
        $failureRate = 0.0;

        if ($latestRun !== null) {
            $latestRunStatus = (string) $latestRun->status;
            $latestRunDurationMs = $latestRun->duration_ms !== null ? (int) $latestRun->duration_ms : null;

            $events = $memory->metricEvents()
                ->where('run_id', $latestRun->run_id)
                ->orderBy('id')
                ->get();

            $terminalEventsBySection = $events
                ->filter(fn ($event): bool => $event->technical_memory_section_id !== null)
                ->groupBy('technical_memory_section_id')
                ->map(fn ($sectionEvents) => $sectionEvents
                    ->whereIn('event_type', [TechnicalMemoryMetrics::EVENT_COMPLETED, TechnicalMemoryMetrics::EVENT_FAILED])
                    ->last())
                ->filter();

            $durations = $terminalEventsBySection
                ->pluck('duration_ms')
                ->filter(fn ($duration): bool => is_int($duration));

            if ($durations->isNotEmpty()) {
                $avgSectionDurationMs = (int) round($durations->avg());
            }

            $sectionsTotal = (int) $latestRun->sections_total;

            if ($sectionsTotal > 0) {
                $completedOnFirstPass = $terminalEventsBySection
                    ->filter(fn ($event): bool => $event->event_type === TechnicalMemoryMetrics::EVENT_COMPLETED && $event->attempt === 1)
                    ->count();

                $firstPassRate = round(($completedOnFirstPass / $sectionsTotal) * 100, 1);
                $retryRate = round((((int) $latestRun->sections_retried) / $sectionsTotal) * 100, 1);
                $failureRate = round((((int) $latestRun->sections_failed) / $sectionsTotal) * 100, 1);
            }
        }

        $criteriaByGroup = $tender->extractedCriteria
            ->where('criterion_type', 'judgment')
            ->groupBy(fn ($criterion): string => (string) ($criterion->group_key ?? ''));

        $sections = $memory->sections
            ->sortBy('sort_order')
            ->map(function ($section) use ($criteriaByGroup): array {
                $anchor = 'section-'.$section->id;
                $groupKey = (string) ($section->group_key ?? '');
                $criteria = $criteriaByGroup->get($groupKey, collect());

                $evidence = $criteria
                    ->map(function ($criterion): array {
                        $label = trim((string) ($criterion->section_number ?? ''));
                        $label = $label !== '' ? $label : 'Criterio';

                        $points = $criterion->score_points !== null
                            ? number_format((float) $criterion->score_points, 2, ',', '.').' pts'
                            : 'Puntos N/D';

                        return [
                            'label' => $label.' · '.$points,
                            'detail' => trim((string) $criterion->description),
                            'reference' => $criterion->source_reference !== null ? trim((string) $criterion->source_reference) : null,
                        ];
                    })
                    ->filter(fn (array $item): bool => $item['detail'] !== '')
                    ->unique('detail')
                    ->take(3)
                    ->values()
                    ->all();

                return [
                    'id' => $section->id,
                    'anchor' => $anchor,
                    'title' => SectionTitleNormalizer::heading($section->section_number, (string) $section->section_title),
                    'content' => trim((string) ($section->content ?? '')),
                    'points' => (float) ($section->total_points ?? 0),
                    'weight' => (float) ($section->weight_percent ?? 0),
                    'criteria_count' => (int) ($section->criteria_count ?? 0),
                    'status' => $section->status instanceof TechnicalMemorySectionStatus
                        ? $section->status->value
                        : (string) $section->status,
                    'evidence' => $evidence,
                ];
            })
            ->values()
            ->all();

        $completedCount = count(array_filter(
            $sections,
            static fn (array $section): bool => $section['status'] === TechnicalMemorySectionStatus::Completed->value,
        ));

        $inProgressSections = array_values(array_filter(
            $sections,
            static fn (array $section): bool => in_array($section['status'], TechnicalMemorySectionStatus::activeValues(), true),
        ));

        $pendingSections = array_values(array_filter(
            $sections,
            static fn (array $section): bool => $section['status'] === TechnicalMemorySectionStatus::Pending->value,
        ));

        $generatingSections = array_values(array_filter(
            $sections,
            static fn (array $section): bool => $section['status'] === TechnicalMemorySectionStatus::Generating->value,
        ));

        $failedSections = array_values(array_filter(
            $sections,
            static fn (array $section): bool => $section['status'] === TechnicalMemorySectionStatus::Failed->value,
        ));

        $pendingCount = count($pendingSections);
        $generatingCount = count($generatingSections);
        $failedCount = count($failedSections);

        $totalCount = count($sections);
        $totalPoints = array_reduce(
            $sections,
            static fn (float $carry, array $section): float => $carry + (float) $section['points'],
            0.0,
        );

        $progressPercent = $totalCount > 0
            ? (int) round(($completedCount / $totalCount) * 100)
            : 0;

        return new self(
            hasMemory: true,
            isGenerating: $memory->status === 'draft',
            sections: $sections,
            inProgressSections: $inProgressSections,
            pendingSections: $pendingSections,
            generatingSections: $generatingSections,
            failedSections: $failedSections,
            pendingCount: $pendingCount,
            generatingCount: $generatingCount,
            failedCount: $failedCount,
            completedCount: $completedCount,
            totalCount: $totalCount,
            totalPoints: $totalPoints,
            progressPercent: $progressPercent,
            markdownExport: TechnicalMemoryMarkdownBuilder::build($memory),
            latestRunStatus: $latestRunStatus,
            latestRunDurationMs: $latestRunDurationMs,
            avgSectionDurationMs: $avgSectionDurationMs,
            firstPassRate: $firstPassRate,
            retryRate: $retryRate,
            failureRate: $failureRate,
        );
    }
}
