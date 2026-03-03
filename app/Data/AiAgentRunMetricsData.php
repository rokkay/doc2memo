<?php

declare(strict_types=1);

namespace App\Data;

final readonly class AiAgentRunMetricsData
{
    public function __construct(
        public string $key,
        public string $modelName,
        public int $inputChars,
        public int $outputChars,
        public string $status,
    ) {}
}
