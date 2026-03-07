<?php

declare(strict_types=1);

namespace App\Actions\Documents;

use App\Models\Document;
use App\Models\DocumentInsight;

final class PersistInsightsAction
{
    /**
     * @param  array<int,array<string,mixed>>  $insights
     */
    public function __invoke(Document $document, array $insights): int
    {
        $count = 0;

        foreach ($insights as $insight) {
            DocumentInsight::query()->create([
                'tender_id' => $document->tender_id,
                'document_id' => $document->id,
                'section_reference' => $insight['section_reference'] ?? null,
                'topic' => (string) ($insight['topic'] ?? 'General'),
                'requirement_type' => (string) ($insight['requirement_type'] ?? 'technical'),
                'importance' => (string) ($insight['importance'] ?? 'medium'),
                'statement' => (string) ($insight['statement'] ?? ''),
                'evidence_excerpt' => (string) ($insight['evidence_excerpt'] ?? ''),
                'metadata' => $insight['metadata'] ?? null,
            ]);
            $count++;
        }

        return $count;
    }
}
