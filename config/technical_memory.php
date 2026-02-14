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
];
