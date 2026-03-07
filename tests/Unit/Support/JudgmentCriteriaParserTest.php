<?php

declare(strict_types=1);

use App\Support\JudgmentCriteriaParser;

it('handles explicit subcriterion checks and empty title formatting', function (): void {
    $parser = new JudgmentCriteriaParser;

    expect($parser->hasExplicitSubcriterionNumber(null))->toBeFalse()
        ->and($parser->hasExplicitSubcriterionNumber('1.2'))->toBeTrue()
        ->and($parser->formatSubcriterionTitle('   '))->toBe('');
});

it('returns empty expansion when there are not enough descriptive matches', function (): void {
    $parser = new JudgmentCriteriaParser;

    $expanded = $parser->expandGroupedJudgmentCriterion('Texto libre sin patrones numericos ni semanticos');

    expect($expanded)->toBeArray()->toHaveCount(0);
});

it('builds normalized group key from number and title', function (): void {
    $parser = new JudgmentCriteriaParser;

    expect($parser->buildGroupKey(' 2.1 ', 'Gestión del equipo & RACI'))->toBe('2.1-gestion-del-equipo-raci');
});

it('covers semantic score and numeric parsing branches via reflection', function (): void {
    $parser = new JudgmentCriteriaParser;

    $resolveSemanticScores = new ReflectionMethod(JudgmentCriteriaParser::class, 'resolveSemanticScores');

    $withoutTotal = $resolveSemanticScores->invoke($parser, ['1.1', '2.2'], null);
    expect($withoutTotal)->toBeArray();

    $scaled = $resolveSemanticScores->invoke($parser, ['1.1', '2.2'], 24.0);
    expect($scaled)->toBeArray()
        ->and($scaled['1.1'])->toBeFloat()
        ->and($scaled['2.2'])->toBeFloat();

    $zeroMappedTotal = $resolveSemanticScores->invoke($parser, ['9.9'], 10.0);
    expect($zeroMappedTotal)->toBe(['9.9' => null]);

    $parseNumericValue = new ReflectionMethod(JudgmentCriteriaParser::class, 'parseNumericValue');

    expect($parseNumericValue->invoke($parser, 5))->toBe(5.0)
        ->and($parseNumericValue->invoke($parser, 5.25))->toBe(5.25)
        ->and($parseNumericValue->invoke($parser, []))->toBeNull()
        ->and($parseNumericValue->invoke($parser, ''))->toBeNull()
        ->and($parseNumericValue->invoke($parser, 'abc'))->toBeNull()
        ->and($parseNumericValue->invoke($parser, '12,5 puntos'))->toBe(12.5);
});
