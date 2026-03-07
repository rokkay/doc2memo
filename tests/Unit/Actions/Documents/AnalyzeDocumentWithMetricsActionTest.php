<?php

declare(strict_types=1);

use App\Actions\Documents\AnalyzeDocumentWithMetricsAction;

it('covers source reference fallback and serialized char estimation helpers', function (): void {
    $action = new AnalyzeDocumentWithMetricsAction;

    $resolveSourceReference = new ReflectionMethod(AnalyzeDocumentWithMetricsAction::class, 'resolveSourceReference');

    expect($resolveSourceReference->invoke($action, null, '', ['source_reference' => ' Ref-10 ']))->toBe('Ref-10')
        ->and($resolveSourceReference->invoke($action, '2.1', '', []))->toBe('2.1')
        ->and($resolveSourceReference->invoke($action, null, 'Titulo', []))->toBe('Titulo');

    $estimateSerializedChars = new ReflectionMethod(AnalyzeDocumentWithMetricsAction::class, 'estimateSerializedChars');

    expect($estimateSerializedChars->invoke($action, ['ok' => 'texto']))->toBeGreaterThan(0)
        ->and($estimateSerializedChars->invoke($action, ['invalid' => INF]))->toBe(0);
});
