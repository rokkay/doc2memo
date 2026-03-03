<?php

declare(strict_types=1);

use App\Actions\Documents\ExtractDocumentTextAction;
use App\Models\Document;
use Illuminate\Support\Facades\Storage;
use Smalot\PdfParser\Parser;
use Tests\TestCase;

uses(TestCase::class);

it('reads markdown and text files directly', function (): void {
    Storage::fake('local');

    $filePath = 'documents/10/brief.md';
    $expected = "# Scope\n\nBody";

    Storage::disk('local')->put($filePath, $expected);

    $document = new Document([
        'file_path' => $filePath,
    ]);

    $text = (new ExtractDocumentTextAction)($document);

    expect($text)->toBe($expected);
});

it('parses non text files through the pdf parser', function (): void {
    Storage::fake('local');

    $filePath = 'documents/20/spec.pdf';
    Storage::disk('local')->put($filePath, 'binary-content');

    $document = new Document([
        'file_path' => $filePath,
    ]);

    $pdf = \Mockery::mock();
    $pdf->shouldReceive('getText')->once()->andReturn('parsed text');

    $parser = \Mockery::mock('overload:'.Parser::class);
    $parser->shouldReceive('parseFile')->once()->andReturn($pdf);

    $text = (new ExtractDocumentTextAction)($document);

    expect($text)->toBe('parsed text');
});
