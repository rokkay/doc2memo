<?php

namespace App\Jobs;

use App\Actions\Documents\ProcessDocumentAction;
use App\Models\Document;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class ProcessDocument implements ShouldQueue
{
    use Queueable;

    public function __construct(public Document $document) {}

    public function handle(?ProcessDocumentAction $processDocumentAction = null): void
    {
        $processDocumentAction ??= resolve(ProcessDocumentAction::class);

        try {
            $processDocumentAction($this->document);
        } catch (\Exception $e) {
            Log::error('Document processing failed', [
                'document_id' => $this->document->id,
                'error' => $e->getMessage(),
            ]);

            $this->document->update([
                'status' => 'failed',
                'processing_error' => $e->getMessage(),
                'extracted_text' => 'Error: '.$e->getMessage(),
            ]);

            $this->document->tender->update(['status' => 'failed']);

            throw $e;
        }
    }
}
