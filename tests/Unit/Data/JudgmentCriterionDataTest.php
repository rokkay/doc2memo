<?php

declare(strict_types=1);

use App\Data\JudgmentCriterionData;

it('normalizes nullable float fields from mixed payload values', function (): void {
    $data = JudgmentCriterionData::fromArray([
        'section_title' => 'Titulo',
        'description' => 'Descripcion',
        'score_points' => ['invalid'],
        'confidence' => 0.8,
    ]);

    expect($data->scorePoints)->toBeNull()
        ->and($data->confidence)->toBe(0.8);
});
