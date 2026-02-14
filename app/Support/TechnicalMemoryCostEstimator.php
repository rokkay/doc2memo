<?php

declare(strict_types=1);

namespace App\Support;

final class TechnicalMemoryCostEstimator
{
    private AiCostEstimator $estimator;

    /**
     * @param  array<string,array<string,int|float>>|null  $models
     */
    public function __construct(?array $models = null)
    {
        $this->estimator = new AiCostEstimator($models);
    }

    /**
     * @return array{estimated_input_units:float,estimated_output_units:float,estimated_cost_usd:float}
     */
    public function estimate(string $model, int $inputChars, int $outputChars): array
    {
        return $this->estimator->estimate(
            model: $model,
            inputChars: $inputChars,
            outputChars: $outputChars,
        );
    }
}
