<?php

declare(strict_types=1);

use App\Support\TechnicalMemoryCostEstimator;

it('estimates input output units and total cost for gpt-5-mini', function (): void {
    $estimation = (new TechnicalMemoryCostEstimator(models: [
        'gpt-5-mini' => [
            'input_price_per_unit_usd' => 0.5,
            'output_price_per_unit_usd' => 1.0,
            'unit_basis_chars' => 4_000_000,
        ],
        'gpt-5.2' => [
            'input_price_per_unit_usd' => 0.9,
            'output_price_per_unit_usd' => 1.5,
            'unit_basis_chars' => 4_000_000,
        ],
    ]))->estimate(
        model: 'gpt-5-mini',
        inputChars: 8_000,
        outputChars: 2_000,
    );

    expect($estimation)->toBe([
        'estimated_input_units' => 0.002,
        'estimated_output_units' => 0.0005,
        'estimated_cost_usd' => 0.0015,
    ]);
});

it('estimates input output units and total cost for gpt-5.2', function (): void {
    $estimation = (new TechnicalMemoryCostEstimator(models: [
        'gpt-5-mini' => [
            'input_price_per_unit_usd' => 0.5,
            'output_price_per_unit_usd' => 1.0,
            'unit_basis_chars' => 4_000_000,
        ],
        'gpt-5.2' => [
            'input_price_per_unit_usd' => 0.9,
            'output_price_per_unit_usd' => 1.5,
            'unit_basis_chars' => 4_000_000,
        ],
    ]))->estimate(
        model: 'gpt-5.2',
        inputChars: 12_000,
        outputChars: 4_000,
    );

    expect($estimation)->toBe([
        'estimated_input_units' => 0.003,
        'estimated_output_units' => 0.001,
        'estimated_cost_usd' => 0.0042,
    ]);
});

it('keeps backward-compatible unsupported model exception', function (): void {
    expect(fn (): array => (new TechnicalMemoryCostEstimator(models: [
        'gpt-5-mini' => [
            'input_price_per_unit_usd' => 0.5,
            'output_price_per_unit_usd' => 1.0,
            'unit_basis_chars' => 4_000_000,
        ],
    ]))->estimate(
        model: 'gpt-unknown',
        inputChars: 100,
        outputChars: 100,
    ))
        ->toThrow(\InvalidArgumentException::class, 'Unsupported model [gpt-unknown] for cost estimation.');
});
