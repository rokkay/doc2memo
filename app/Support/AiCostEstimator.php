<?php

declare(strict_types=1);

namespace App\Support;

use InvalidArgumentException;
use Throwable;

final class AiCostEstimator
{
    /**
     * @param  array<string,array<string,int|float>>|null  $models
     */
    public function __construct(private ?array $models = null) {}

    /**
     * @return array{estimated_input_units:float,estimated_output_units:float,estimated_cost_usd:float}
     */
    public function estimate(string $model, int $inputChars, int $outputChars): array
    {
        $models = $this->resolveModels();
        $modelConfig = $models[$model] ?? null;

        if (! is_array($modelConfig)) {
            throw new InvalidArgumentException("Unsupported model [{$model}] for cost estimation.");
        }

        $unitBasisChars = max(1, (int) ($modelConfig['unit_basis_chars'] ?? 1));
        $inputUnits = round(max(0, $inputChars) / $unitBasisChars, 4);
        $outputUnits = round(max(0, $outputChars) / $unitBasisChars, 4);
        $inputPricePerUnitUsd = (float) ($modelConfig['input_price_per_unit_usd'] ?? 0.0);
        $outputPricePerUnitUsd = (float) ($modelConfig['output_price_per_unit_usd'] ?? 0.0);
        $estimatedCostUsd = round(($inputUnits * $inputPricePerUnitUsd) + ($outputUnits * $outputPricePerUnitUsd), 6);

        return [
            'estimated_input_units' => $inputUnits,
            'estimated_output_units' => $outputUnits,
            'estimated_cost_usd' => $estimatedCostUsd,
        ];
    }

    /**
     * @return array<string,array<string,int|float>>
     */
    private function resolveModels(): array
    {
        if (is_array($this->models)) {
            return $this->models;
        }

        try {
            $configuredModels = config('technical_memory.cost.models', []);

            if (is_array($configuredModels)) {
                return $configuredModels;
            }
        } catch (Throwable) {
            return [];
        }

        return [];
    }
}
