<?php

declare(strict_types=1);

return [
    'quality_gate' => [
        'min_chars' => 1800,
        'min_h3' => 3,
        'relative_min_factor' => 0.45,
        'max_retry_attempts' => 1,
    ],

    'style_editor' => [
        'enabled' => true,
    ],

    'metrics' => [
        'retention_days' => 90,
    ],

    'cost' => [
        'models' => [
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
        ],
    ],
];
