<?php

declare(strict_types=1);

use App\Support\AiCostEstimator;

it('falls back to empty models when config helper is unavailable', function (): void {
    $estimator = new AiCostEstimator;

    expect(fn (): array => $estimator->estimate('unknown-model', 10, 10))
        ->toThrow(InvalidArgumentException::class);
});
