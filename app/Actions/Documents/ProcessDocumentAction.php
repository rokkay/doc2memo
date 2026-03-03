<?php

declare(strict_types=1);

namespace App\Actions\Documents;

use App\Data\JudgmentCriterionData;
use App\Enums\AiCostCategory;
use App\Models\AiCostEntry;
use App\Models\Document;
use App\Models\DocumentInsight;
use App\Models\ExtractedCriterion;
use App\Models\ExtractedSpecification;
use App\Support\JudgmentCriteriaParser;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Smalot\PdfParser\Parser;

final readonly class ProcessDocumentAction
{
    public function __construct(private ?JudgmentCriteriaParser $judgmentCriteriaParser = new JudgmentCriteriaParser) {}

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

        $analysisPayload = resolve(AnalyzeDocumentWithMetricsAction::class)($document, $text);
        $analysis = $analysisPayload['analysis'];
        $costSummary = $analysisPayload['costSummary'];
        $dedicatedCriteria = $analysisPayload['dedicatedCriteria'];

        DB::transaction(function () use ($document, $analysis, $costSummary, $dedicatedCriteria): void {
            $this->clearPreviousExtractions($document);

            if ($document->document_type === 'pca') {
                $this->storePcaData($document, $analysis, $dedicatedCriteria);
            }

            if ($document->document_type === 'ppt') {
                $this->storePptData($document, $analysis);
            }

            $insightsCount = $this->storeInsights($document, $analysis['insights'] ?? []);

            $this->storeAiCostEntries(
                document: $document,
                breakdown: is_array($costSummary['breakdown'] ?? null) ? $costSummary['breakdown'] : [],
            );

            $document->update([
                'status' => 'analyzed',
                'insights_count' => $insightsCount,
                'processing_error' => null,
                'analyzed_at' => now(),
            ]);

            $this->refreshTenderStatus($document);
        });
    }

    /**
     * @param  array<string,mixed>  $breakdown
     */
    private function storeAiCostEntries(Document $document, array $breakdown): void
    {
        $categoryByAgent = [
            'document_analyzer' => AiCostCategory::DocumentAnalyzer,
            'dedicated_judgment_extractor' => AiCostCategory::DedicatedJudgmentExtractor,
        ];

        foreach ($categoryByAgent as $agentKey => $category) {
            $agentBreakdown = $breakdown[$agentKey] ?? null;

            if (! is_array($agentBreakdown)) {
                continue;
            }

            $tokenUsage = is_array($agentBreakdown['token_usage'] ?? null)
                ? $agentBreakdown['token_usage']
                : [];

            AiCostEntry::query()->create([
                'tender_id' => $document->tender_id,
                'document_id' => $document->id,
                'run_id' => null,
                'attempt' => null,
                'category' => $category,
                'agent_key' => $agentKey,
                'model_name' => $agentBreakdown['model_name'] ?? null,
                'status' => (string) ($agentBreakdown['status'] ?? 'unknown'),
                'input_chars' => max(0, (int) ($agentBreakdown['input_chars'] ?? 0)),
                'output_chars' => max(0, (int) ($agentBreakdown['output_chars'] ?? 0)),
                'prompt_tokens' => max(0, (int) ($tokenUsage['prompt_tokens'] ?? 0)),
                'completion_tokens' => max(0, (int) ($tokenUsage['completion_tokens'] ?? 0)),
                'cache_write_input_tokens' => max(0, (int) ($tokenUsage['cache_write_input_tokens'] ?? 0)),
                'cache_read_input_tokens' => max(0, (int) ($tokenUsage['cache_read_input_tokens'] ?? 0)),
                'reasoning_tokens' => max(0, (int) ($tokenUsage['reasoning_tokens'] ?? 0)),
                'estimated_input_units' => (float) ($agentBreakdown['estimated_input_units'] ?? 0),
                'estimated_output_units' => (float) ($agentBreakdown['estimated_output_units'] ?? 0),
                'estimated_cost_usd' => (float) ($agentBreakdown['estimated_cost_usd'] ?? 0),
                'metadata' => [
                    'char_estimate_fallback' => $agentBreakdown['char_estimate_fallback'] ?? null,
                    'token_usage_available' => (bool) ($tokenUsage['available'] ?? false),
                ],
            ]);
        }
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

    private function clearPreviousExtractions(Document $document): void
    {
        $document->extractedCriteria()->delete();
        $document->extractedSpecifications()->delete();
        $document->insights()->delete();
    }

    /**
     * @param  array<int,JudgmentCriterionData>  $dedicatedJudgmentCriteria
     */
    private function storePcaData(Document $document, array $analysis, array $dedicatedJudgmentCriteria): void
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

        $criteria = is_array($analysis['criteria'] ?? null) ? $analysis['criteria'] : [];

        foreach ($criteria as $criterion) {
            $criterionType = $this->normalizeCriterionType(
                type: $criterion['criterion_type'] ?? null,
                sectionTitle: (string) ($criterion['section_title'] ?? ''),
                description: (string) ($criterion['description'] ?? ''),
            );

            $normalizedCriterion = JudgmentCriterionData::fromArray([
                'section_number' => $criterion['section_number'] ?? null,
                'section_title' => (string) ($criterion['section_title'] ?? 'Sin sección'),
                'description' => (string) ($criterion['description'] ?? ''),
                'priority' => (string) ($criterion['priority'] ?? 'mandatory'),
                'criterion_type' => $criterionType,
                'score_points' => $criterion['score_points'] ?? null,
                'source' => 'analyzer',
                'confidence' => 0.70,
                'source_reference' => $this->resolveSourceReference(
                    sectionNumber: is_string($criterion['section_number'] ?? null) ? $criterion['section_number'] : null,
                    sectionTitle: (string) ($criterion['section_title'] ?? ''),
                    metadata: is_array($criterion['metadata'] ?? null) ? $criterion['metadata'] : [],
                ),
                'metadata' => is_array($criterion['metadata'] ?? null) ? $criterion['metadata'] : null,
            ]);

            $expandedCriteria = $this->expandJudgmentSubcriteria($normalizedCriterion);
            $criteriaToPersist = $expandedCriteria !== [] ? $expandedCriteria : [$normalizedCriterion];

            foreach ($criteriaToPersist as $criterionItem) {
                $sectionNumber = $criterionItem->sectionNumber;
                $sectionTitle = $criterionItem->sectionTitle;

                ExtractedCriterion::query()->create([
                    'tender_id' => $document->tender_id,
                    'document_id' => $document->id,
                    'section_number' => $sectionNumber,
                    'section_title' => $sectionTitle,
                    'description' => $criterionItem->description,
                    'priority' => $criterionItem->priority,
                    'criterion_type' => $criterionItem->criterionType,
                    'score_points' => $this->extractScorePoints(
                        scorePoints: $criterionItem->scorePoints,
                        description: $criterionItem->description,
                        metadata: $criterionItem->metadata ?? [],
                    ),
                    'source' => $criterionItem->source,
                    'confidence' => $criterionItem->confidence,
                    'source_reference' => $criterionItem->sourceReference,
                    'group_key' => $this->buildGroupKey(
                        sectionNumber: $sectionNumber,
                        sectionTitle: $sectionTitle,
                    ),
                    'metadata' => $criterionItem->metadata,
                ]);
            }
        }

        foreach ($dedicatedJudgmentCriteria as $criterionItem) {
            $groupKey = $this->buildGroupKey(
                sectionNumber: $criterionItem->sectionNumber,
                sectionTitle: $criterionItem->sectionTitle,
            );

            ExtractedCriterion::query()->updateOrCreate(
                [
                    'document_id' => $document->id,
                    'criterion_type' => 'judgment',
                    'group_key' => $groupKey,
                ],
                [
                    'tender_id' => $document->tender_id,
                    'section_number' => $criterionItem->sectionNumber,
                    'section_title' => $criterionItem->sectionTitle,
                    'description' => $criterionItem->description,
                    'priority' => $criterionItem->priority,
                    'score_points' => $this->extractScorePoints(
                        scorePoints: $criterionItem->scorePoints,
                        description: $criterionItem->description,
                        metadata: $criterionItem->metadata ?? [],
                    ),
                    'source' => $criterionItem->source,
                    'confidence' => $criterionItem->confidence,
                    'source_reference' => $criterionItem->sourceReference,
                    'metadata' => $criterionItem->metadata,
                ],
            );
        }
    }

    /**
     * @return array<int,JudgmentCriterionData>
     */
    private function expandJudgmentSubcriteria(JudgmentCriterionData $criterion): array
    {
        if ($criterion->criterionType !== 'judgment') {
            return [];
        }

        if ($this->judgmentCriteriaParser->hasExplicitSubcriterionNumber($criterion->sectionNumber)) {
            return [];
        }

        $subcriteria = $this->judgmentCriteriaParser->expandGroupedJudgmentCriterion(
            description: $criterion->description,
            totalJudgmentPoints: $criterion->scorePoints,
        );

        if ($subcriteria === []) {
            return [];
        }

        return collect($subcriteria)
            ->map(fn (array $subcriterion): JudgmentCriterionData => JudgmentCriterionData::fromArray([
                'section_number' => $subcriterion['section_number'] !== '' ? $subcriterion['section_number'] : $criterion->sectionNumber,
                'section_title' => $subcriterion['section_title'] !== '' ? $subcriterion['section_title'] : $criterion->sectionTitle,
                'description' => $subcriterion['section_title'] !== '' ? $subcriterion['section_title'] : $criterion->description,
                'priority' => $criterion->priority,
                'criterion_type' => 'judgment',
                'score_points' => $subcriterion['score_points'],
                'source' => 'parser',
                'confidence' => 0.65,
                'source_reference' => $criterion->sourceReference,
                'metadata' => $criterion->metadata,
            ]))
            ->all();
    }

    /**
     * @param  array<string,mixed>  $metadata
     */
    private function resolveSourceReference(?string $sectionNumber, string $sectionTitle, array $metadata): ?string
    {
        $fromMetadata = collect([
            $metadata['source_reference'] ?? null,
            $metadata['section_reference'] ?? null,
            $metadata['reference'] ?? null,
            $metadata['page'] ?? null,
            $metadata['page_reference'] ?? null,
        ])->first(fn (mixed $value): bool => is_string($value) && trim($value) !== '');

        if (is_string($fromMetadata) && trim($fromMetadata) !== '') {
            return trim($fromMetadata);
        }

        $number = trim((string) $sectionNumber);
        $title = trim($sectionTitle);

        if ($number !== '' && $title !== '') {
            return $number.' '.$title;
        }

        if ($number !== '') {
            return $number;
        }

        return $title !== '' ? $title : null;
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

    /**
     * @param  array<string,mixed>  $metadata
     */
    private function extractScorePoints(mixed $scorePoints, string $description, array $metadata): ?float
    {
        $normalizedScore = $this->parseNumericValue($scorePoints);

        if ($normalizedScore !== null) {
            return $normalizedScore;
        }

        $metadataScore = collect([
            $metadata['score_points'] ?? null,
            $metadata['points'] ?? null,
            $metadata['puntos'] ?? null,
            $metadata['puntuacion'] ?? null,
            $metadata['puntuación'] ?? null,
            $metadata['max_points'] ?? null,
            $metadata['max_puntos'] ?? null,
            $metadata['weight_points'] ?? null,
        ])
            ->map(fn (mixed $value): ?float => $this->parseNumericValue($value))
            ->first(fn (?float $value): bool => $value !== null);

        if ($metadataScore !== null) {
            return $metadataScore;
        }

        if (preg_match('/(?:hasta\s+)?(\d+(?:[\.,]\d+)?)\s*(?:puntos?|pts?\.?)/iu', $description, $matches) === 1) {
            return $this->parseNumericValue($matches[1]);
        }

        return null;
    }

    private function normalizeCriterionType(mixed $type, string $sectionTitle, string $description): string
    {
        $normalizedType = Str::of((string) $type)->trim()->lower()->toString();
        $source = Str::of($sectionTitle.' '.$description)->lower()->toString();

        if (preg_match('/condiciones\s+especiales\s+de\s+ejecuci[oó]n|art\.\s*202\s*lcsp|subcontratistas|igualdad\s+de\s+remuneraci[oó]n/u', $source) === 1) {
            return 'automatic';
        }

        if (preg_match('/criterios?\s+b\s*\(?juicio\s+de\s+valor\)?|sobre\s*b|juicio\s+de\s+valor/u', $source) === 1) {
            return 'judgment';
        }

        if (in_array($normalizedType, ['judgment', 'automatic'], true)) {
            return $normalizedType;
        }

        if (preg_match('/juicio\s+de\s+valor/u', $source) === 1) {
            return 'judgment';
        }

        if (preg_match('/autom[aá]tic|f[oó]rmula|precio|coste|horas/u', $source) === 1) {
            return 'automatic';
        }

        return 'judgment';
    }

    private function buildGroupKey(?string $sectionNumber, string $sectionTitle): string
    {
        return $this->judgmentCriteriaParser->buildGroupKey($sectionNumber, $sectionTitle);
    }

    private function parseNumericValue(mixed $value): ?float
    {
        if (is_int($value) || is_float($value)) {
            return (float) $value;
        }

        if (! is_string($value)) {
            return null;
        }

        $normalized = str_replace(',', '.', trim($value));

        if ($normalized === '') {
            return null;
        }

        if (preg_match('/-?\d+(?:\.\d+)?/', $normalized, $matches) !== 1) {
            return null;
        }

        return (float) $matches[0];
    }
}
