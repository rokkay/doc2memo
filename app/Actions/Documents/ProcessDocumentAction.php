<?php

declare(strict_types=1);

namespace App\Actions\Documents;

use App\Ai\Agents\DocumentAnalyzer;
use App\Ai\Agents\PcaJudgmentCriteriaExtractorAgent;
use App\Data\JudgmentCriterionData;
use App\Models\Document;
use App\Models\DocumentInsight;
use App\Models\ExtractedCriterion;
use App\Models\ExtractedSpecification;
use App\Support\JudgmentCriteriaParser;
use App\Support\TechnicalMemoryCostEstimator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Smalot\PdfParser\Parser;

final class ProcessDocumentAction
{
    private JudgmentCriteriaParser $judgmentCriteriaParser;

    public function __construct(?JudgmentCriteriaParser $judgmentCriteriaParser = null)
    {
        $this->judgmentCriteriaParser = $judgmentCriteriaParser ?? new JudgmentCriteriaParser;
    }

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

        $costEstimator = resolve(TechnicalMemoryCostEstimator::class);
        $documentAnalyzer = new DocumentAnalyzer($document->document_type);
        $analysisAgentMetrics = [
            'model_name' => $documentAnalyzer->modelName(),
            'input_chars' => max(0, $documentAnalyzer->estimateInputChars($text)),
            'output_chars' => 0,
            'status' => 'pending',
        ];
        $dedicatedExtractorMetrics = [
            'model_name' => PcaJudgmentCriteriaExtractorAgent::MODEL_NAME,
            'input_chars' => 0,
            'output_chars' => 0,
            'status' => $document->document_type === 'pca' ? 'pending' : 'skipped',
        ];

        $analysis = $this->analyzeText($documentAnalyzer, $text);
        $analysisAgentMetrics['output_chars'] = $this->estimateSerializedChars($analysis);
        $analysisAgentMetrics['status'] = 'completed';

        DB::transaction(function () use ($document, $analysis, $text, $analysisAgentMetrics, &$dedicatedExtractorMetrics, $costEstimator): void {
            $this->clearPreviousExtractions($document);

            if ($document->document_type === 'pca') {
                $this->storePcaData($document, $analysis, $text, $dedicatedExtractorMetrics);
            }

            if ($document->document_type === 'ppt') {
                $this->storePptData($document, $analysis);
            }

            $insightsCount = $this->storeInsights($document, $analysis['insights'] ?? []);
            $analysisCost = $this->estimateAnalysisCost(
                analysisAgentMetrics: $analysisAgentMetrics,
                dedicatedExtractorMetrics: $dedicatedExtractorMetrics,
                estimator: $costEstimator,
            );

            $document->update([
                'status' => 'analyzed',
                'insights_count' => $insightsCount,
                'estimated_analysis_input_units' => $analysisCost['estimated_input_units'],
                'estimated_analysis_output_units' => $analysisCost['estimated_output_units'],
                'estimated_analysis_cost_usd' => $analysisCost['estimated_cost_usd'],
                'analysis_cost_breakdown' => $analysisCost['breakdown'],
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

    private function analyzeText(DocumentAnalyzer $documentAnalyzer, string $text): array
    {
        return $documentAnalyzer->analyze($text);
    }

    private function clearPreviousExtractions(Document $document): void
    {
        $document->extractedCriteria()->delete();
        $document->extractedSpecifications()->delete();
        $document->insights()->delete();
    }

    /**
     * @param  array{model_name:string,input_chars:int,output_chars:int,status:string}  $dedicatedExtractorMetrics
     */
    private function storePcaData(Document $document, array $analysis, string $sourceText, array &$dedicatedExtractorMetrics): void
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
        $dedicatedJudgmentCriteria = $this->extractDedicatedJudgmentCriteria($sourceText, $criteria, $dedicatedExtractorMetrics);

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
     * @param  array<int,mixed>  $criteria
     * @param  array{model_name:string,input_chars:int,output_chars:int,status:string}  $dedicatedExtractorMetrics
     * @return array<int,JudgmentCriterionData>
     */
    private function extractDedicatedJudgmentCriteria(string $sourceText, array $criteria, array &$dedicatedExtractorMetrics): array
    {
        if (! $this->shouldRunDedicatedJudgmentExtractor($criteria)) {
            $dedicatedExtractorMetrics['status'] = 'skipped';

            return [];
        }

        $extractor = new PcaJudgmentCriteriaExtractorAgent;
        $dedicatedExtractorMetrics['model_name'] = $extractor->modelName();
        $dedicatedExtractorMetrics['input_chars'] = max(0, $extractor->estimateInputChars($sourceText));

        try {
            $items = $extractor->extract($sourceText);
            $dedicatedExtractorMetrics['output_chars'] = $this->estimateSerializedChars(['criteria' => $items]);
            $dedicatedExtractorMetrics['status'] = 'completed';

            return collect($items)
                ->filter(fn (mixed $item): bool => is_array($item))
                ->map(fn (array $item): JudgmentCriterionData => JudgmentCriterionData::fromArray([
                    'section_number' => $item['section_number'] ?? null,
                    'section_title' => $item['section_title'] ?? 'Sin sección',
                    'description' => $item['description'] ?? '',
                    'priority' => $item['priority'] ?? 'mandatory',
                    'criterion_type' => 'judgment',
                    'score_points' => $item['score_points'] ?? null,
                    'source' => 'dedicated_extractor',
                    'confidence' => 0.95,
                    'source_reference' => $this->resolveSourceReference(
                        sectionNumber: is_string($item['section_number'] ?? null) ? $item['section_number'] : null,
                        sectionTitle: (string) ($item['section_title'] ?? ''),
                        metadata: is_array($item['metadata'] ?? null) ? $item['metadata'] : [],
                    ),
                    'group_key' => '',
                    'metadata' => is_array($item['metadata'] ?? null) ? $item['metadata'] : null,
                ]))
                ->filter(fn (JudgmentCriterionData $item): bool => $item->sectionTitle !== '' && $item->description !== '')
                ->values()
                ->all();
        } catch (\Throwable $exception) {
            $dedicatedExtractorMetrics['status'] = 'failed';

            Log::warning('Dedicated judgment criteria extraction failed, using fallback criteria.', [
                'error' => $exception->getMessage(),
            ]);

            return [];
        }
    }

    /**
     * @param  array<int,mixed>  $criteria
     */
    private function shouldRunDedicatedJudgmentExtractor(array $criteria): bool
    {
        return collect($criteria)
            ->filter(fn (mixed $criterion): bool => is_array($criterion))
            ->contains(function (array $criterion): bool {
                $content = Str::of(
                    (string) ($criterion['section_number'] ?? '')
                    .' '.(string) ($criterion['section_title'] ?? '')
                    .' '.(string) ($criterion['description'] ?? '')
                )->lower()->toString();

                return preg_match('/juicio\s+de\s+valor|sobre\s*b|criterios?\s+b/u', $content) === 1;
            });
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
            ->map(function (array $subcriterion) use ($criterion): JudgmentCriterionData {
                return JudgmentCriterionData::fromArray([
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
                ]);
            })
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

    /**
     * @param  array{model_name:string,input_chars:int,output_chars:int,status:string}  $analysisAgentMetrics
     * @param  array{model_name:string,input_chars:int,output_chars:int,status:string}  $dedicatedExtractorMetrics
     * @return array{estimated_input_units:float,estimated_output_units:float,estimated_cost_usd:float,breakdown:array<string,array<string,int|float|string>>}
     */
    private function estimateAnalysisCost(
        array $analysisAgentMetrics,
        array $dedicatedExtractorMetrics,
        TechnicalMemoryCostEstimator $estimator,
    ): array {
        $analysisEstimate = $estimator->estimate(
            model: $analysisAgentMetrics['model_name'],
            inputChars: $analysisAgentMetrics['input_chars'],
            outputChars: $analysisAgentMetrics['output_chars'],
        );
        $dedicatedEstimate = $estimator->estimate(
            model: $dedicatedExtractorMetrics['model_name'],
            inputChars: $dedicatedExtractorMetrics['input_chars'],
            outputChars: $dedicatedExtractorMetrics['output_chars'],
        );

        return [
            'estimated_input_units' => round($analysisEstimate['estimated_input_units'] + $dedicatedEstimate['estimated_input_units'], 4),
            'estimated_output_units' => round($analysisEstimate['estimated_output_units'] + $dedicatedEstimate['estimated_output_units'], 4),
            'estimated_cost_usd' => round($analysisEstimate['estimated_cost_usd'] + $dedicatedEstimate['estimated_cost_usd'], 6),
            'breakdown' => [
                'document_analyzer' => [
                    'model_name' => $analysisAgentMetrics['model_name'],
                    'input_chars' => $analysisAgentMetrics['input_chars'],
                    'output_chars' => $analysisAgentMetrics['output_chars'],
                    'estimated_input_units' => $analysisEstimate['estimated_input_units'],
                    'estimated_output_units' => $analysisEstimate['estimated_output_units'],
                    'estimated_cost_usd' => $analysisEstimate['estimated_cost_usd'],
                    'status' => $analysisAgentMetrics['status'],
                ],
                'dedicated_judgment_extractor' => [
                    'model_name' => $dedicatedExtractorMetrics['model_name'],
                    'input_chars' => $dedicatedExtractorMetrics['input_chars'],
                    'output_chars' => $dedicatedExtractorMetrics['output_chars'],
                    'estimated_input_units' => $dedicatedEstimate['estimated_input_units'],
                    'estimated_output_units' => $dedicatedEstimate['estimated_output_units'],
                    'estimated_cost_usd' => $dedicatedEstimate['estimated_cost_usd'],
                    'status' => $dedicatedExtractorMetrics['status'],
                ],
            ],
        ];
    }

    /**
     * @param  array<mixed>  $payload
     */
    private function estimateSerializedChars(array $payload): int
    {
        $encoded = json_encode($payload, JSON_UNESCAPED_UNICODE);

        if (! is_string($encoded)) {
            return 0;
        }

        return max(0, mb_strlen($encoded));
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
