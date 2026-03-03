<?php

declare(strict_types=1);

namespace App\Actions\Documents;

use App\Models\Document;
use App\Models\ExtractedSpecification;

final class PersistPptExtractionAction
{
    /**
     * @param  array<string,mixed>  $analysis
     */
    public function __invoke(Document $document, array $analysis): void
    {
        foreach (($analysis['specifications'] ?? []) as $specification) {
            ExtractedSpecification::query()->create([
                'tender_id' => $document->tender_id,
                'document_id' => $document->id,
                'section_number' => $specification['section_number'] ?? null,
                'section_title' => (string) ($specification['section_title'] ?? 'Sin sección'),
                'technical_description' => (string) ($specification['technical_description'] ?? ''),
                'requirements' => (string) ($specification['requirements'] ?? ''),
                'deliverables' => (string) ($specification['deliverables'] ?? ''),
                'metadata' => $specification['metadata'] ?? null,
            ]);
        }
    }
}
