<?php

declare(strict_types=1);

namespace App\Actions\Documents;

use App\Ai\Agents\DocumentAnalyzer;
use App\Models\Document;
use Laravel\Ai\Responses\AgentResponse;
use Throwable;

final class QueueDocumentAnalysisAction
{
    public function __invoke(Document $document): void
    {
        if (! in_array($document->status, ['uploaded', 'failed'], true)) {
            return;
        }

        $document->update([
            'status' => 'processing',
            'processing_error' => null,
        ]);

        $text = resolve(ExtractDocumentTextAction::class)($document);

        $documentId = $document->id;

        (new DocumentAnalyzer($document->document_type))
            ->queue($text)
            ->then(static function (AgentResponse $response) use ($documentId): void {
                resolve(ApplyQueuedDocumentAnalysisSuccessAction::class)($documentId, $response);
            })
            ->catch(static function (Throwable $exception) use ($documentId): void {
                resolve(ApplyQueuedDocumentAnalysisFailureAction::class)($documentId, $exception);
            });
    }
}
