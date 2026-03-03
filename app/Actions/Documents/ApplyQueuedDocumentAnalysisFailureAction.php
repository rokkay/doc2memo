<?php

declare(strict_types=1);

namespace App\Actions\Documents;

use App\Models\Document;
use Illuminate\Support\Facades\Log;
use Throwable;

final class ApplyQueuedDocumentAnalysisFailureAction
{
    public function __invoke(int $documentId, Throwable $exception): void
    {
        $document = Document::query()
            ->with('tender')
            ->find($documentId);

        if (! $document) {
            return;
        }

        Log::error('Document processing failed', [
            'document_id' => $document->id,
            'error' => $exception->getMessage(),
        ]);

        $document->update([
            'status' => 'failed',
            'processing_error' => $exception->getMessage(),
            'extracted_text' => 'Error: '.$exception->getMessage(),
        ]);

        $document->tender->update(['status' => 'failed']);
    }
}
