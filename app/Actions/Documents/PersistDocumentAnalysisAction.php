<?php

declare(strict_types=1);

namespace App\Actions\Documents;

use App\Data\JudgmentCriterionData;
use App\Models\Document;
use Illuminate\Support\Facades\DB;

final class PersistDocumentAnalysisAction
{
    public function __construct(
        private ?PersistPcaExtractionAction $persistPcaExtractionAction = null,
        private ?PersistPptExtractionAction $persistPptExtractionAction = null,
        private ?PersistInsightsAction $persistInsightsAction = null,
        private ?PersistAiCostEntriesAction $persistAiCostEntriesAction = null,
        private ?RefreshTenderStatusAction $refreshTenderStatusAction = null,
    ) {}

    /**
     * @param  array<string,mixed>  $analysis
     * @param  array{breakdown?:array<string,mixed>}  $costSummary
     * @param  array<int,JudgmentCriterionData>  $dedicatedCriteria
     */
    public function __invoke(Document $document, array $analysis, array $costSummary, array $dedicatedCriteria): void
    {
        DB::transaction(function () use ($document, $analysis, $costSummary, $dedicatedCriteria): void {
            $this->clearPreviousExtractions($document);

            if ($document->document_type === 'pca') {
                ($this->pcaPersistence())($document, $analysis, $dedicatedCriteria);
            }

            if ($document->document_type === 'ppt') {
                ($this->pptPersistence())($document, $analysis);
            }

            $insightsCount = ($this->insightsPersistence())($document, $analysis['insights'] ?? []);

            ($this->aiCostPersistence())(
                document: $document,
                breakdown: is_array($costSummary['breakdown'] ?? null) ? $costSummary['breakdown'] : [],
            );

            $document->update([
                'status' => 'analyzed',
                'insights_count' => $insightsCount,
                'processing_error' => null,
                'analyzed_at' => now(),
            ]);

            ($this->tenderStatusRefresh())($document);
        });
    }

    private function clearPreviousExtractions(Document $document): void
    {
        $document->extractedCriteria()->delete();
        $document->extractedSpecifications()->delete();
        $document->insights()->delete();
    }

    private function pcaPersistence(): PersistPcaExtractionAction
    {
        return $this->persistPcaExtractionAction ??= resolve(PersistPcaExtractionAction::class);
    }

    private function pptPersistence(): PersistPptExtractionAction
    {
        return $this->persistPptExtractionAction ??= resolve(PersistPptExtractionAction::class);
    }

    private function insightsPersistence(): PersistInsightsAction
    {
        return $this->persistInsightsAction ??= resolve(PersistInsightsAction::class);
    }

    private function aiCostPersistence(): PersistAiCostEntriesAction
    {
        return $this->persistAiCostEntriesAction ??= resolve(PersistAiCostEntriesAction::class);
    }

    private function tenderStatusRefresh(): RefreshTenderStatusAction
    {
        return $this->refreshTenderStatusAction ??= resolve(RefreshTenderStatusAction::class);
    }
}
