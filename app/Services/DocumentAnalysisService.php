<?php

namespace App\Services;

use App\Actions\Documents\QueueDocumentAnalysisAction;
use App\Actions\Tenders\AnalyzeTenderDocumentsAction;
use App\Models\Document;
use App\Models\Tender;

class DocumentAnalysisService
{
    public function __construct(
        private readonly AnalyzeTenderDocumentsAction $analyzeTenderDocumentsAction,
        private readonly QueueDocumentAnalysisAction $queueDocumentAnalysisAction,
    ) {}

    public function analyzeTender(Tender $tender): void
    {
        ($this->analyzeTenderDocumentsAction)($tender);
    }

    public function analyzeDocument(Tender $tender, Document $document): void
    {
        if (! $document->tender->is($tender)) {
            return;
        }

        if (! in_array($document->status, ['uploaded', 'failed'], true)) {
            return;
        }

        ($this->queueDocumentAnalysisAction)($document);
        $tender->update(['status' => 'analyzing']);
    }
}
