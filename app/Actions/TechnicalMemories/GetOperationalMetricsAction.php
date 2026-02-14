<?php

declare(strict_types=1);

namespace App\Actions\TechnicalMemories;

use App\Data\TechnicalMemoryOperationalMetricsData;
use App\Models\TechnicalMemoryGenerationMetric;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;

final class GetOperationalMetricsAction
{
    public function __invoke(CarbonInterface $from, CarbonInterface $to): TechnicalMemoryOperationalMetricsData
    {
        $metrics = TechnicalMemoryGenerationMetric::query()
            ->with(['technicalMemory:id,title', 'technicalMemorySection:id,section_title'])
            ->whereBetween('created_at', [$from, $to])
            ->orderBy('created_at')
            ->get();

        return new TechnicalMemoryOperationalMetricsData(
            global: $this->buildGlobalKpis($metrics),
            dailyTrend: $this->buildDailyTrend($metrics),
            memories: $this->buildMemorySummaries($metrics),
            topProblematicSections: $this->buildTopProblematicSections($metrics),
        );
    }

    /**
     * @param  Collection<int,TechnicalMemoryGenerationMetric>  $metrics
     * @return array<string,int|float>
     */
    private function buildGlobalKpis(Collection $metrics): array
    {
        $attempts = $metrics->count();
        $firstPassCount = $metrics->where('status', 'completed')->where('attempt', 1)->count();
        $retryCount = $metrics->filter(fn (TechnicalMemoryGenerationMetric $metric): bool => $metric->attempt > 1)->count();
        $failureCount = $metrics->where('status', 'failed')->count();
        $avgDurationMs = $attempts > 0 ? (int) round((float) $metrics->avg('duration_ms')) : 0;

        return [
            'attempts' => $attempts,
            'first_pass_rate' => $this->rate($firstPassCount, $attempts),
            'retry_rate' => $this->rate($retryCount, $attempts),
            'failure_rate' => $this->rate($failureCount, $attempts),
            'avg_duration_ms' => $avgDurationMs,
            'p95_duration_ms' => $this->p95Duration($metrics),
            'estimated_cost_usd' => round((float) $metrics->sum('estimated_cost_usd'), 6),
        ];
    }

    /**
     * @param  Collection<int,TechnicalMemoryGenerationMetric>  $metrics
     * @return array<int,array<string,int|float|string>>
     */
    private function buildDailyTrend(Collection $metrics): array
    {
        return $metrics
            ->groupBy(fn (TechnicalMemoryGenerationMetric $metric): string => $metric->created_at->toDateString())
            ->map(function (Collection $dayMetrics, string $date): array {
                $attempts = $dayMetrics->count();
                $firstPass = $dayMetrics->where('status', 'completed')->where('attempt', 1)->count();
                $retries = $dayMetrics->filter(fn (TechnicalMemoryGenerationMetric $metric): bool => $metric->attempt > 1)->count();
                $failures = $dayMetrics->where('status', 'failed')->count();

                return [
                    'date' => $date,
                    'attempts' => $attempts,
                    'first_pass_rate' => $this->rate($firstPass, $attempts),
                    'retry_rate' => $this->rate($retries, $attempts),
                    'failure_rate' => $this->rate($failures, $attempts),
                    'estimated_cost_usd' => round((float) $dayMetrics->sum('estimated_cost_usd'), 6),
                ];
            })
            ->sortBy('date')
            ->values()
            ->all();
    }

    /**
     * @param  Collection<int,TechnicalMemoryGenerationMetric>  $metrics
     * @return array<int,array<string,int|float|string>>
     */
    private function buildMemorySummaries(Collection $metrics): array
    {
        return $metrics
            ->groupBy('technical_memory_id')
            ->map(function (Collection $memoryMetrics, int $memoryId): array {
                $first = $memoryMetrics->first();

                return [
                    'technical_memory_id' => $memoryId,
                    'memory_title' => (string) ($first?->technicalMemory?->title ?? ''),
                    'attempts' => $memoryMetrics->count(),
                    'completed' => $memoryMetrics->where('status', 'completed')->count(),
                    'failed' => $memoryMetrics->where('status', 'failed')->count(),
                    'retried' => $memoryMetrics->filter(fn (TechnicalMemoryGenerationMetric $metric): bool => $metric->attempt > 1)->count(),
                    'estimated_cost_usd' => round((float) $memoryMetrics->sum('estimated_cost_usd'), 6),
                ];
            })
            ->sortByDesc('attempts')
            ->values()
            ->all();
    }

    /**
     * @param  Collection<int,TechnicalMemoryGenerationMetric>  $metrics
     * @return array<int,array<string,int|float|string>>
     */
    private function buildTopProblematicSections(Collection $metrics): array
    {
        return $metrics
            ->groupBy('technical_memory_section_id')
            ->map(function (Collection $sectionMetrics, int $sectionId): array {
                $first = $sectionMetrics->first();
                $retryCount = $sectionMetrics->filter(fn (TechnicalMemoryGenerationMetric $metric): bool => $metric->attempt > 1)->count();
                $failureCount = $sectionMetrics->where('status', 'failed')->count();

                return [
                    'technical_memory_section_id' => $sectionId,
                    'section_title' => (string) ($first?->technicalMemorySection?->section_title ?? ''),
                    'retry_count' => $retryCount,
                    'failure_count' => $failureCount,
                    'issue_score' => $retryCount + $failureCount,
                ];
            })
            ->filter(fn (array $row): bool => $row['issue_score'] > 0)
            ->sortByDesc('issue_score')
            ->values()
            ->all();
    }

    private function rate(int $numerator, int $denominator): float
    {
        if ($denominator === 0) {
            return 0.0;
        }

        return round(($numerator / $denominator) * 100, 2);
    }

    /**
     * @param  Collection<int,TechnicalMemoryGenerationMetric>  $metrics
     */
    private function p95Duration(Collection $metrics): int
    {
        $values = $metrics
            ->pluck('duration_ms')
            ->map(fn (mixed $value): int => (int) $value)
            ->sort()
            ->values();

        $count = $values->count();

        if ($count === 0) {
            return 0;
        }

        $rank = (int) ceil($count * 0.95);

        return $values[max(0, $rank - 1)];
    }
}
