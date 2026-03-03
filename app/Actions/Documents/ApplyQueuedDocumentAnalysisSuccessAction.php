<?php

declare(strict_types=1);

namespace App\Actions\Documents;

use App\Ai\Agents\DocumentAnalyzer;
use App\Models\Document;

final class ApplyQueuedDocumentAnalysisSuccessAction
{
    public function __invoke(int $documentId, mixed $response): void
    {
        $document = Document::query()->find($documentId);

        if (! $document) {
            return;
        }

        $sourceText = resolve(ExtractDocumentTextAction::class)($document);

        $analysis = (new DocumentAnalyzer($document->document_type))
            ->normalizeResponse($response);

        $analysisPayload = resolve(AnalyzeDocumentWithMetricsAction::class)
            ->fromNormalizedAnalysis($document, $sourceText, $analysis);

        resolve(ProcessDocumentAction::class)($document, $analysisPayload);
    }
}
