<?php

declare(strict_types=1);

use App\Data\AiAgentRunMetricsData;
use App\Support\AiCostBreakdownCalculator;
use App\Support\AiCostEstimator;

it('calculates totals for mixed agent statuses with deterministic breakdown', function (): void {
    $calculator = new AiCostBreakdownCalculator(new AiCostEstimator(models: [
        'gpt-5-mini' => [
            'input_price_per_unit_usd' => 0.5,
            'output_price_per_unit_usd' => 1.0,
            'unit_basis_chars' => 4_000_000,
        ],
    ]));

    $result = $calculator->calculate([
        new AiAgentRunMetricsData(
            key: 'dynamic_section',
            modelName: 'gpt-5-mini',
            inputChars: 8_000,
            outputChars: 2_000,
            status: 'completed',
        ),
        new AiAgentRunMetricsData(
            key: 'style_editor',
            modelName: 'gpt-5-mini',
            inputChars: 4_000,
            outputChars: 1_000,
            status: 'failed',
        ),
        new AiAgentRunMetricsData(
            key: 'dedicated_judgment_extractor',
            modelName: 'gpt-5-mini',
            inputChars: 0,
            outputChars: 0,
            status: 'skipped',
        ),
    ]);

    expect($result)->toBe([
        'estimated_input_units' => 0.003,
        'estimated_output_units' => 0.0008,
        'estimated_cost_usd' => 0.0023,
        'breakdown' => [
            'dynamic_section' => [
                'model_name' => 'gpt-5-mini',
                'input_chars' => 8000,
                'output_chars' => 2000,
                'estimated_input_units' => 0.002,
                'estimated_output_units' => 0.0005,
                'estimated_cost_usd' => 0.0015,
                'status' => 'completed',
            ],
            'style_editor' => [
                'model_name' => 'gpt-5-mini',
                'input_chars' => 4000,
                'output_chars' => 1000,
                'estimated_input_units' => 0.001,
                'estimated_output_units' => 0.0003,
                'estimated_cost_usd' => 0.0008,
                'status' => 'failed',
            ],
            'dedicated_judgment_extractor' => [
                'model_name' => 'gpt-5-mini',
                'input_chars' => 0,
                'output_chars' => 0,
                'estimated_input_units' => 0.0,
                'estimated_output_units' => 0.0,
                'estimated_cost_usd' => 0.0,
                'status' => 'skipped',
            ],
        ],
    ]);
});
