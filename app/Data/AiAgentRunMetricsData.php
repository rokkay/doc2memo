<?php

declare(strict_types=1);

namespace App\Data;

final class AiAgentRunMetricsData
{
    public function __construct(
        public readonly string $key,
        public readonly string $modelName,
        public readonly int $inputChars,
        public readonly int $outputChars,
        public readonly string $status,
    ) {}
}
