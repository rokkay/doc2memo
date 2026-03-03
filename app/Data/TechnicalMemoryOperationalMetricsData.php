<?php

declare(strict_types=1);

namespace App\Data;

final readonly class TechnicalMemoryOperationalMetricsData
{
    /**
     * @param  array<string,int|float>  $global
     * @param  array<int,array<string,int|float|string>>  $dailyTrend
     * @param  array<int,array<string,int|float|string>>  $memories
     * @param  array<int,array<string,int|float|string>>  $topProblematicSections
     * @param  array<string,int|float>  $documentAnalysis
     */
    public function __construct(
        public array $global,
        public array $dailyTrend,
        public array $memories,
        public array $topProblematicSections,
        public array $documentAnalysis,
    ) {}
}
