<?php

declare(strict_types=1);

use App\Ai\Agents\DocumentAnalyzer;
use Tests\TestCase;

uses(TestCase::class);

it('returns pca response shape for pca documents', function (): void {
    DocumentAnalyzer::fake([
        [
            'tender_info' => ['title' => 'Contrato'],
            'criteria' => [],
            'insights' => [],
        ],
    ])->preventStrayPrompts();

    $result = (new DocumentAnalyzer('pca'))->analyze('contenido');

    expect($result)
        ->toHaveKeys(['tender_info', 'criteria', 'insights'])
        ->and($result)->not->toHaveKey('specifications');
});

it('returns ppt response shape for ppt documents', function (): void {
    DocumentAnalyzer::fake([
        [
            'specifications' => [],
            'insights' => [],
        ],
    ])->preventStrayPrompts();

    $result = (new DocumentAnalyzer('ppt'))->analyze('contenido');

    expect($result)
        ->toHaveKeys(['specifications', 'insights'])
        ->and($result)->not->toHaveKey('tender_info')
        ->and($result)->not->toHaveKey('criteria');
});
