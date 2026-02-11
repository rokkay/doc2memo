<?php

declare(strict_types=1);

it('defines closed metadata objects in structured schema', function (): void {
    $source = file_get_contents(__DIR__.'/../../../app/Ai/Agents/DocumentAnalyzer.php');

    expect($source)->not->toBeFalse();
    expect(substr_count(
        (string) $source,
        "'metadata' => \$schema->object()->withoutAdditionalProperties()"
    ))->toBeGreaterThanOrEqual(2);
});
