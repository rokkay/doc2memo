<?php

declare(strict_types=1);

use App\Support\AiCostEstimator;
use Tests\TestCase;

uses(TestCase::class);

it('returns empty model list fallback when no models are resolvable', function (): void {
    $estimator = new AiCostEstimator;

    expect(fn (): array => $estimator->estimate('unknown-model', 1000, 1000))
        ->toThrow(InvalidArgumentException::class);
});

it('returns empty array when config models are not an array', function (): void {
    config()->set('technical_memory.cost.models', 'invalid');

    $estimator = new AiCostEstimator;

    expect(fn (): array => $estimator->estimate('unknown-model', 10, 10))
        ->toThrow(InvalidArgumentException::class);
});

it('estimates costs from explicit model config', function (): void {
    $estimator = new AiCostEstimator([
        'gpt-test' => [
            'unit_basis_chars' => 1000,
            'input_price_per_unit_usd' => 0.1,
            'output_price_per_unit_usd' => 0.2,
        ],
    ]);

    $result = $estimator->estimate('gpt-test', 1500, 500);

    expect($result['estimated_input_units'])->toBe(1.5)
        ->and($result['estimated_output_units'])->toBe(0.5)
        ->and($result['estimated_cost_usd'])->toBe(0.25);
});
