<?php

declare(strict_types=1);

namespace App\Support;

use App\Data\AiAgentRunMetricsData;

final class AiCostBreakdownCalculator
{
    public function __construct(private ?AiCostEstimator $estimator = null) {}

    /**
     * @param  array<int,AiAgentRunMetricsData>  $agentRuns
     * @return array{estimated_input_units:float,estimated_output_units:float,estimated_cost_usd:float,breakdown:array<string,array{model_name:string,input_chars:int,output_chars:int,estimated_input_units:float,estimated_output_units:float,estimated_cost_usd:float,status:string}>}
     */
    public function calculate(array $agentRuns): array
    {
        $estimatedInputUnits = 0.0;
        $estimatedOutputUnits = 0.0;
        $estimatedCostUsd = 0.0;
        $breakdown = [];

        foreach ($agentRuns as $agentRun) {
            $estimate = $this->costEstimator()->estimate(
                model: $agentRun->modelName,
                inputChars: $agentRun->inputChars,
                outputChars: $agentRun->outputChars,
            );

            $estimatedInputUnits += $estimate['estimated_input_units'];
            $estimatedOutputUnits += $estimate['estimated_output_units'];
            $estimatedCostUsd += $estimate['estimated_cost_usd'];

            $breakdown[$agentRun->key] = [
                'model_name' => $agentRun->modelName,
                'input_chars' => max(0, $agentRun->inputChars),
                'output_chars' => max(0, $agentRun->outputChars),
                'estimated_input_units' => $estimate['estimated_input_units'],
                'estimated_output_units' => $estimate['estimated_output_units'],
                'estimated_cost_usd' => $estimate['estimated_cost_usd'],
                'status' => $agentRun->status,
            ];
        }

        return [
            'estimated_input_units' => round($estimatedInputUnits, 4),
            'estimated_output_units' => round($estimatedOutputUnits, 4),
            'estimated_cost_usd' => round($estimatedCostUsd, 6),
            'breakdown' => $breakdown,
        ];
    }

    private function costEstimator(): AiCostEstimator
    {
        return $this->estimator ??= new AiCostEstimator;
    }
}
