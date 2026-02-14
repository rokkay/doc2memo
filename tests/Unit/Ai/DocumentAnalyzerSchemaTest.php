<?php

declare(strict_types=1);

it('defines closed metadata objects in structured schema', function (): void {
    $pcaSource = file_get_contents(__DIR__.'/../../../app/Ai/Agents/DocumentAnalysis/PcaDocumentAnalyzerDefinition.php');
    $pptSource = file_get_contents(__DIR__.'/../../../app/Ai/Agents/DocumentAnalysis/PptDocumentAnalyzerDefinition.php');

    expect($pcaSource)->not->toBeFalse();
    expect($pptSource)->not->toBeFalse();

    $closedMetadataPattern = "'metadata' => \$schema->object()->withoutAdditionalProperties()";

    expect(
        substr_count((string) $pcaSource, $closedMetadataPattern)
        + substr_count((string) $pptSource, $closedMetadataPattern)
    )->toBeGreaterThanOrEqual(2);
});
