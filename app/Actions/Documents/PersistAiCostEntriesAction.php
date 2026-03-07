<?php

declare(strict_types=1);

namespace App\Actions\Documents;

use App\Enums\AiCostCategory;
use App\Models\AiCostEntry;
use App\Models\Document;

final class PersistAiCostEntriesAction
{
    /**
     * @param  array<string,mixed>  $breakdown
     */
    public function __invoke(Document $document, array $breakdown): void
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
}
