<?php

declare(strict_types=1);

namespace App\Actions\Tenders;

use App\Models\Tender;

final class AnalyzeTenderDocumentsAction
{
    public function __invoke(Tender $tender): void
    {
        $documents = $tender->documents()
            ->whereIn('status', ['uploaded', 'failed'])
            ->get();

        foreach ($documents as $document) {
            $document->update([
                'status' => 'processing',
                'processing_error' => null,
            ]);

            dispatch(new \App\Jobs\ProcessDocument($document));
        }

        if ($documents->isNotEmpty()) {
            $tender->update(['status' => 'analyzing']);
        }
    }
}
