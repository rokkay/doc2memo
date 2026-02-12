<?php

declare(strict_types=1);

namespace App\Actions\Documents;

use App\Ai\Agents\DocumentAnalyzer;
use App\Models\Document;
use App\Models\DocumentInsight;
use App\Models\ExtractedCriterion;
use App\Models\ExtractedSpecification;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Smalot\PdfParser\Parser;

final class ProcessDocumentAction
{
    public function __invoke(Document $document): void
    {
        $document->update([
            'status' => 'processing',
            'processing_error' => null,
        ]);

        $text = $this->extractText($document);

        $document->update([
            'extracted_text' => mb_substr($text, 0, 10000),
        ]);

        $analysis = $this->analyzeText($document->document_type, $text);

        DB::transaction(function () use ($document, $analysis): void {
            $this->clearPreviousExtractions($document);

            if ($document->document_type === 'pca') {
                $this->storePcaData($document, $analysis);
            }

            if ($document->document_type === 'ppt') {
                $this->storePptData($document, $analysis);
            }

            $insightsCount = $this->storeInsights($document, $analysis['insights'] ?? []);

            $document->update([
                'status' => 'analyzed',
                'insights_count' => $insightsCount,
                'processing_error' => null,
                'analyzed_at' => now(),
            ]);

            $this->refreshTenderStatus($document);
        });
    }

    private function extractText(Document $document): string
    {
        $filePath = Storage::path($document->file_path);
        $extension = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));

        if (in_array($extension, ['md', 'txt'], true)) {
            return file_get_contents($filePath) ?: '';
        }

        $parser = new Parser;
        $pdf = $parser->parseFile($filePath);

        return $pdf->getText();
    }

    private function analyzeText(string $documentType, string $text): array
    {
        return (new DocumentAnalyzer($documentType))->analyze($text);
    }

    private function clearPreviousExtractions(Document $document): void
    {
        $document->extractedCriteria()->delete();
        $document->extractedSpecifications()->delete();
        $document->insights()->delete();
    }

    private function storePcaData(Document $document, array $analysis): void
    {
        $tenderInfo = $analysis['tender_info'] ?? [];

        if ($tenderInfo !== []) {
            $currentTender = $document->tender;

            $newTenderValues = [
                'title' => (string) ($tenderInfo['title'] ?? $currentTender->getRawOriginal('title')),
                'issuing_company' => (string) ($tenderInfo['issuing_company'] ?? $currentTender->getRawOriginal('issuing_company')),
                'reference_number' => (string) ($tenderInfo['reference_number'] ?? $currentTender->getRawOriginal('reference_number')),
                'deadline_date' => (string) ($tenderInfo['deadline_date'] ?? $currentTender->getRawOriginal('deadline_date')),
                'description' => (string) ($tenderInfo['description'] ?? $currentTender->getRawOriginal('description')),
            ];

            $document->tender()
                ->getQuery()
                ->whereKey($document->tender_id)
                ->update($newTenderValues);

            $document->unsetRelation('tender');
        }

        foreach (($analysis['criteria'] ?? []) as $criterion) {
            ExtractedCriterion::query()->create([
                'tender_id' => $document->tender_id,
                'document_id' => $document->id,
                'section_number' => $criterion['section_number'] ?? null,
                'section_title' => (string) ($criterion['section_title'] ?? 'Sin sección'),
                'description' => (string) ($criterion['description'] ?? ''),
                'priority' => (string) ($criterion['priority'] ?? 'mandatory'),
                'metadata' => $criterion['metadata'] ?? null,
            ]);
        }
    }

    private function storePptData(Document $document, array $analysis): void
    {
        foreach (($analysis['specifications'] ?? []) as $specification) {
            ExtractedSpecification::query()->create([
                'tender_id' => $document->tender_id,
                'document_id' => $document->id,
                'section_number' => $specification['section_number'] ?? null,
                'section_title' => (string) ($specification['section_title'] ?? 'Sin sección'),
                'technical_description' => (string) ($specification['technical_description'] ?? ''),
                'requirements' => (string) ($specification['requirements'] ?? ''),
                'deliverables' => (string) ($specification['deliverables'] ?? ''),
                'metadata' => $specification['metadata'] ?? null,
            ]);
        }
    }

    private function storeInsights(Document $document, array $insights): int
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

    private function refreshTenderStatus(Document $document): void
    {
        $tender = $document->tender->fresh();

        if ($tender->documents()->where('status', 'failed')->exists()) {
            $tender->update(['status' => 'failed']);

            return;
        }

        if ($tender->documents()->where('status', '!=', 'analyzed')->exists()) {
            $tender->update(['status' => 'analyzing']);

            return;
        }

        $tender->update(['status' => 'completed']);
    }
}
