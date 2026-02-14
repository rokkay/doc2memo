<?php

declare(strict_types=1);

namespace App\Actions\Documents;

use App\Ai\Agents\DocumentAnalyzer;
use App\Ai\Agents\PcaJudgmentCriteriaExtractorAgent;
use App\Data\AiAgentRunMetricsData;
use App\Data\JudgmentCriterionData;
use App\Listeners\RecordAiUsageFromAgentPrompted;
use App\Models\Document;
use App\Support\AiCostBreakdownCalculator;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

final class AnalyzeDocumentWithMetricsAction
{
    public function __construct(private ?AiCostBreakdownCalculator $costBreakdownCalculator = null) {}

    /**
     * @return array{analysis:array<string,mixed>,costSummary:array{estimated_input_units:float,estimated_output_units:float,estimated_cost_usd:float,breakdown:array<string,array{model_name:string,input_chars:int,output_chars:int,estimated_input_units:float,estimated_output_units:float,estimated_cost_usd:float,status:string}>},dedicatedCriteria:array<int,JudgmentCriterionData>}
     */
    public function __invoke(Document $document, string $text): array
    {
        $documentAnalyzer = new DocumentAnalyzer($document->document_type);
        $analysisAgentMetrics = [
            'model_name' => $documentAnalyzer->modelName(),
            'input_chars' => max(0, $documentAnalyzer->estimateInputChars($text)),
            'output_chars' => 0,
            'status' => 'pending',
            'usage' => null,
        ];
        $dedicatedExtractorMetrics = [
            'model_name' => PcaJudgmentCriteriaExtractorAgent::MODEL_NAME,
            'input_chars' => 0,
            'output_chars' => 0,
            'status' => $document->document_type === 'pca' ? 'pending' : 'skipped',
            'usage' => null,
        ];

        $analysis = $documentAnalyzer->analyze($text);
        $analysisAgentMetrics['output_chars'] = $this->estimateSerializedChars($analysis);
        $analysisAgentMetrics['status'] = 'completed';
        $analysisAgentMetrics['usage'] = RecordAiUsageFromAgentPrompted::pullUsageForAgent(DocumentAnalyzer::class);

        $dedicatedCriteria = [];

        if ($document->document_type === 'pca') {
            $criteria = is_array($analysis['criteria'] ?? null) ? $analysis['criteria'] : [];
            $dedicatedCriteria = $this->extractDedicatedJudgmentCriteria($text, $criteria, $dedicatedExtractorMetrics);
        }

        $costSummary = $this->calculator()->calculate([
            new AiAgentRunMetricsData(
                key: 'document_analyzer',
                modelName: $analysisAgentMetrics['model_name'],
                inputChars: $analysisAgentMetrics['input_chars'],
                outputChars: $analysisAgentMetrics['output_chars'],
                status: $analysisAgentMetrics['status'],
            ),
            new AiAgentRunMetricsData(
                key: 'dedicated_judgment_extractor',
                modelName: $dedicatedExtractorMetrics['model_name'],
                inputChars: $dedicatedExtractorMetrics['input_chars'],
                outputChars: $dedicatedExtractorMetrics['output_chars'],
                status: $dedicatedExtractorMetrics['status'],
            ),
        ]);
        $costSummary['breakdown']['document_analyzer']['token_usage'] = [
            'available' => is_array($analysisAgentMetrics['usage']),
            'prompt_tokens' => (int) ($analysisAgentMetrics['usage']['prompt_tokens'] ?? 0),
            'completion_tokens' => (int) ($analysisAgentMetrics['usage']['completion_tokens'] ?? 0),
            'cache_write_input_tokens' => (int) ($analysisAgentMetrics['usage']['cache_write_input_tokens'] ?? 0),
            'cache_read_input_tokens' => (int) ($analysisAgentMetrics['usage']['cache_read_input_tokens'] ?? 0),
            'reasoning_tokens' => (int) ($analysisAgentMetrics['usage']['reasoning_tokens'] ?? 0),
        ];
        $costSummary['breakdown']['document_analyzer']['char_estimate_fallback'] = [
            'input_chars' => $analysisAgentMetrics['input_chars'],
            'output_chars' => $analysisAgentMetrics['output_chars'],
        ];
        $costSummary['breakdown']['dedicated_judgment_extractor']['token_usage'] = [
            'available' => is_array($dedicatedExtractorMetrics['usage']),
            'prompt_tokens' => (int) ($dedicatedExtractorMetrics['usage']['prompt_tokens'] ?? 0),
            'completion_tokens' => (int) ($dedicatedExtractorMetrics['usage']['completion_tokens'] ?? 0),
            'cache_write_input_tokens' => (int) ($dedicatedExtractorMetrics['usage']['cache_write_input_tokens'] ?? 0),
            'cache_read_input_tokens' => (int) ($dedicatedExtractorMetrics['usage']['cache_read_input_tokens'] ?? 0),
            'reasoning_tokens' => (int) ($dedicatedExtractorMetrics['usage']['reasoning_tokens'] ?? 0),
        ];
        $costSummary['breakdown']['dedicated_judgment_extractor']['char_estimate_fallback'] = [
            'input_chars' => $dedicatedExtractorMetrics['input_chars'],
            'output_chars' => $dedicatedExtractorMetrics['output_chars'],
        ];

        return [
            'analysis' => $analysis,
            'costSummary' => $costSummary,
            'dedicatedCriteria' => $dedicatedCriteria,
        ];
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
            $dedicatedExtractorMetrics['usage'] = RecordAiUsageFromAgentPrompted::pullUsageForAgent(PcaJudgmentCriteriaExtractorAgent::class);

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
            $dedicatedExtractorMetrics['usage'] = RecordAiUsageFromAgentPrompted::pullUsageForAgent(PcaJudgmentCriteriaExtractorAgent::class);

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

    private function calculator(): AiCostBreakdownCalculator
    {
        return $this->costBreakdownCalculator ??= new AiCostBreakdownCalculator;
    }
}
