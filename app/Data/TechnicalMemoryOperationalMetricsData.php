<?php

declare(strict_types=1);

namespace App\Data;

final class TechnicalMemoryOperationalMetricsData
{
    /**
     * @param  array<string,int|float>  $global
     * @param  array<int,array<string,int|float|string>>  $dailyTrend
     * @param  array<int,array<string,int|float|string>>  $memories
     * @param  array<int,array<string,int|float|string>>  $topProblematicSections
     * @param  array<string,int|float>  $documentAnalysis
     */
    public function __construct(
        public readonly array $global,
        public readonly array $dailyTrend,
        public readonly array $memories,
        public readonly array $topProblematicSections,
        public readonly array $documentAnalysis,
    ) {}
}
