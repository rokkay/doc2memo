<?php

declare(strict_types=1);

namespace App\Actions\Documents;

use App\Models\Document;

final class ProcessDocumentAction
{
    public function __construct(
        private ?ExtractDocumentTextAction $extractDocumentTextAction = null,
        private ?PersistDocumentAnalysisAction $persistDocumentAnalysisAction = null,
    ) {}

    public function __invoke(Document $document): void
    {
        $document->update([
            'status' => 'processing',
            'processing_error' => null,
        ]);

        $text = ($this->extractor())($document);

        $document->update([
            'extracted_text' => mb_substr($text, 0, 10000),
        ]);

        $analysisPayload = resolve(AnalyzeDocumentWithMetricsAction::class)($document, $text);

        ($this->analysisPersistence())(
            document: $document,
            analysis: $analysisPayload['analysis'],
            costSummary: $analysisPayload['costSummary'],
            dedicatedCriteria: $analysisPayload['dedicatedCriteria'],
        );
    }

    private function extractor(): ExtractDocumentTextAction
    {
        return $this->extractDocumentTextAction ??= resolve(ExtractDocumentTextAction::class);
    }

    private function analysisPersistence(): PersistDocumentAnalysisAction
    {
        return $this->persistDocumentAnalysisAction ??= resolve(PersistDocumentAnalysisAction::class);
    }
}
