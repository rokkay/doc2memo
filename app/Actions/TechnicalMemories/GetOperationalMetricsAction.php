<?php

declare(strict_types=1);

namespace App\Actions\TechnicalMemories;

use App\Data\TechnicalMemoryOperationalMetricsData;
use App\Enums\AiCostCategory;
use App\Models\AiCostEntry;
use App\Models\Document;
use App\Models\TechnicalMemoryGenerationMetric;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;

final class GetOperationalMetricsAction
{
    public function __invoke(CarbonInterface $from, CarbonInterface $to): TechnicalMemoryOperationalMetricsData
    {
        $metrics = TechnicalMemoryGenerationMetric::query()
            ->with(['technicalMemory:id,title', 'technicalMemorySection:id,section_title'])
            ->whereBetween('created_at', [$from, $to])->oldest()
            ->get();
        $costEntries = AiCostEntry::query()
            ->whereBetween('created_at', [$from, $to])->oldest()
            ->get();
        $documents = Document::query()
            ->whereBetween('analyzed_at', [$from, $to])
            ->oldest('analyzed_at')
            ->get();

        return new TechnicalMemoryOperationalMetricsData(
            global: $this->buildGlobalKpis($metrics, $documents, $costEntries),
            dailyTrend: $this->buildDailyTrend($metrics, $costEntries),
            memories: $this->buildMemorySummaries($metrics, $costEntries),
            topProblematicSections: $this->buildTopProblematicSections($metrics),
            documentAnalysis: $this->buildDocumentAnalysisSummary($documents, $costEntries),
        );
    }

    /**
     * @param  Collection<int,TechnicalMemoryGenerationMetric>  $metrics
     * @return array<string,int|float>
     */
    private function buildGlobalKpis(Collection $metrics, Collection $documents, Collection $costEntries): array
    {
        $attempts = $metrics->count();
        $firstPassCount = $metrics->where('status', 'completed')->where('attempt', 1)->count();
        $retryCount = $metrics->filter(fn (TechnicalMemoryGenerationMetric $metric): bool => $metric->attempt > 1)->count();
        $failureCount = $metrics->where('status', 'failed')->count();
        $avgDurationMs = $attempts > 0 ? (int) round((float) $metrics->avg('duration_ms')) : 0;
        $generationCostEntries = $this->generationCostEntries($costEntries);
        $documentCostEntries = $this->documentCostEntries($costEntries);

        $dynamicSectionCostUsd = $this->sumCostByCategory($generationCostEntries, AiCostCategory::DynamicSection);
        $styleEditorCostUsd = $this->sumCostByCategory($generationCostEntries, AiCostCategory::StyleEditor);
        $generationTotalCostUsd = round((float) $generationCostEntries->sum('estimated_cost_usd'), 6);
        $documentAnalysisCostUsd = round((float) $documentCostEntries->sum('estimated_cost_usd'), 6);
        $documentAnalyzerCostUsd = $this->sumCostByCategory($documentCostEntries, AiCostCategory::DocumentAnalyzer);
        $dedicatedExtractorCostUsd = $this->sumCostByCategory($documentCostEntries, AiCostCategory::DedicatedJudgmentExtractor);

        return [
            'attempts' => $attempts,
            'first_pass_rate' => $this->rate($firstPassCount, $attempts),
            'retry_rate' => $this->rate($retryCount, $attempts),
            'failure_rate' => $this->rate($failureCount, $attempts),
            'avg_duration_ms' => $avgDurationMs,
            'p95_duration_ms' => $this->p95Duration($metrics),
            'estimated_cost_usd' => $generationTotalCostUsd,
            'estimated_dynamic_cost_usd' => $dynamicSectionCostUsd,
            'estimated_style_editor_cost_usd' => $styleEditorCostUsd,
            'analyzed_documents' => $documents->count(),
            'estimated_document_analysis_cost_usd' => $documentAnalysisCostUsd,
            'estimated_document_analyzer_cost_usd' => $documentAnalyzerCostUsd,
            'estimated_dedicated_extractor_cost_usd' => $dedicatedExtractorCostUsd,
        ];
    }

    /**
     * @param  Collection<int,TechnicalMemoryGenerationMetric>  $metrics
     * @return array<int,array<string,int|float|string>>
     */
    private function buildDailyTrend(Collection $metrics, Collection $costEntries): array
    {
        $generationCostEntries = $this->generationCostEntries($costEntries);

        return $metrics
            ->groupBy(fn (TechnicalMemoryGenerationMetric $metric): string => $metric->created_at->toDateString())
            ->map(function (Collection $dayMetrics, string $date) use ($generationCostEntries): array {
                $attempts = $dayMetrics->count();
                $firstPass = $dayMetrics->where('status', 'completed')->where('attempt', 1)->count();
                $retries = $dayMetrics->filter(fn (TechnicalMemoryGenerationMetric $metric): bool => $metric->attempt > 1)->count();
                $failures = $dayMetrics->where('status', 'failed')->count();
                $dayCostEntries = $generationCostEntries->filter(
                    fn (AiCostEntry $entry): bool => $entry->created_at->toDateString() === $date,
                );

                $dynamicSectionCostUsd = $this->sumCostByCategory($dayCostEntries, AiCostCategory::DynamicSection);
                $styleEditorCostUsd = $this->sumCostByCategory($dayCostEntries, AiCostCategory::StyleEditor);
                $estimatedCostUsd = round((float) $dayCostEntries->sum('estimated_cost_usd'), 6);

                return [
                    'date' => $date,
                    'attempts' => $attempts,
                    'first_pass_rate' => $this->rate($firstPass, $attempts),
                    'retry_rate' => $this->rate($retries, $attempts),
                    'failure_rate' => $this->rate($failures, $attempts),
                    'estimated_cost_usd' => $estimatedCostUsd,
                    'estimated_dynamic_cost_usd' => $dynamicSectionCostUsd,
                    'estimated_style_editor_cost_usd' => $styleEditorCostUsd,
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
    private function buildMemorySummaries(Collection $metrics, Collection $costEntries): array
    {
        $generationCostEntries = $this->generationCostEntries($costEntries);

        return $metrics
            ->groupBy('technical_memory_id')
            ->map(function (Collection $memoryMetrics, int $memoryId) use ($generationCostEntries): array {
                $first = $memoryMetrics->first();
                $memoryCostEntries = $generationCostEntries
                    ->where('technical_memory_id', $memoryId)
                    ->values();

                $dynamicSectionCostUsd = $this->sumCostByCategory($memoryCostEntries, AiCostCategory::DynamicSection);
                $styleEditorCostUsd = $this->sumCostByCategory($memoryCostEntries, AiCostCategory::StyleEditor);
                $estimatedCostUsd = round((float) $memoryCostEntries->sum('estimated_cost_usd'), 6);

                return [
                    'technical_memory_id' => $memoryId,
                    'memory_title' => (string) ($first?->technicalMemory?->title ?? ''),
                    'attempts' => $memoryMetrics->count(),
                    'completed' => $memoryMetrics->where('status', 'completed')->count(),
                    'failed' => $memoryMetrics->where('status', 'failed')->count(),
                    'retried' => $memoryMetrics->filter(fn (TechnicalMemoryGenerationMetric $metric): bool => $metric->attempt > 1)->count(),
                    'estimated_cost_usd' => $estimatedCostUsd,
                    'estimated_dynamic_cost_usd' => $dynamicSectionCostUsd,
                    'estimated_style_editor_cost_usd' => $styleEditorCostUsd,
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

    /**
     * @param  Collection<int,Document>  $documents
     * @return array<string,int|float>
     */
    private function buildDocumentAnalysisSummary(Collection $documents, Collection $costEntries): array
    {
        $documentCostEntries = $this->documentCostEntries($costEntries);
        $documentAnalysisCostUsd = round((float) $documentCostEntries->sum('estimated_cost_usd'), 6);
        $documentAnalyzerCostUsd = $this->sumCostByCategory($documentCostEntries, AiCostCategory::DocumentAnalyzer);
        $dedicatedExtractorCostUsd = $this->sumCostByCategory($documentCostEntries, AiCostCategory::DedicatedJudgmentExtractor);

        return [
            'documents' => $documents->count(),
            'estimated_cost_usd' => $documentAnalysisCostUsd,
            'estimated_document_analyzer_cost_usd' => $documentAnalyzerCostUsd,
            'estimated_dedicated_extractor_cost_usd' => $dedicatedExtractorCostUsd,
        ];
    }

    /**
     * @param  Collection<int,AiCostEntry>  $costEntries
     * @return Collection<int,AiCostEntry>
     */
    private function generationCostEntries(Collection $costEntries): Collection
    {
        return $costEntries
            ->filter(fn (AiCostEntry $entry): bool => in_array($entry->category, [
                AiCostCategory::DynamicSection,
                AiCostCategory::StyleEditor,
            ], true))
            ->values();
    }

    /**
     * @param  Collection<int,AiCostEntry>  $costEntries
     * @return Collection<int,AiCostEntry>
     */
    private function documentCostEntries(Collection $costEntries): Collection
    {
        return $costEntries
            ->filter(fn (AiCostEntry $entry): bool => in_array($entry->category, [
                AiCostCategory::DocumentAnalyzer,
                AiCostCategory::DedicatedJudgmentExtractor,
            ], true))
            ->values();
    }

    /**
     * @param  Collection<int,AiCostEntry>  $costEntries
     */
    private function sumCostByCategory(Collection $costEntries, AiCostCategory $category): float
    {
        return round((float) $costEntries
            ->where('category', $category)
            ->sum('estimated_cost_usd'), 6);
    }
}
