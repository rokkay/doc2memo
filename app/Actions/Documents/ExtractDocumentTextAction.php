<?php

declare(strict_types=1);

namespace App\Actions\Documents;

use App\Models\Document;
use Illuminate\Support\Facades\Storage;
use Smalot\PdfParser\Parser;

final class ExtractDocumentTextAction
{
    public function __invoke(Document $document): string
    {
        $filePath = Storage::path($document->file_path);
        $extension = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));

        if (in_array($extension, ['md', 'txt'], true)) {
            return file_get_contents($filePath) ?: '';
        }

        $parser = new Parser;
        $pdf = $parser->parseFile($filePath);

        return $pdf->getText();
    }
}
