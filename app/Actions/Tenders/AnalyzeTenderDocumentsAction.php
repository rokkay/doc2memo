<?php

declare(strict_types=1);

namespace App\Actions\Tenders;

use App\Actions\Documents\QueueDocumentAnalysisAction;
use App\Models\Tender;

final class AnalyzeTenderDocumentsAction
{
    public function __construct(private readonly QueueDocumentAnalysisAction $queueDocumentAnalysisAction) {}

    public function __invoke(Tender $tender): void
    {
        $documents = $tender->documents()
            ->whereIn('status', ['uploaded', 'failed'])
            ->get();

        foreach ($documents as $document) {
            ($this->queueDocumentAnalysisAction)($document);
        }

        if ($documents->isNotEmpty()) {
            $tender->update(['status' => 'analyzing']);
        }
    }
}
