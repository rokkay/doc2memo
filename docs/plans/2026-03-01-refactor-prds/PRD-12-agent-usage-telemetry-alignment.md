# PRD-12: Agent Usage + Meta Telemetry Alignment

## Problem
AI response telemetry may not consistently persist both token usage and model/provider metadata.

## References
- `vendor/laravel/ai/src/Responses/Data/Usage.php`
- `vendor/laravel/ai/src/Responses/Data/Meta.php`

## Goals
- Use response `usage` as canonical token source.
- Persist response `meta` (`provider`, `model`, citations-derived fields) where available.
- Preserve backward compatibility for sparse provider payloads.

## Scope
- Metrics/event recording paths that ingest AI responses.
- Aggregation/reporting paths consuming persisted telemetry.

## Implementation plan
1. Inventory usage/meta extraction call sites.
2. Introduce one telemetry mapper for usage + meta.
3. Refactor metric-recording actions/listeners to use mapper.
4. Persist completion/cache/reasoning tokens and provider/model consistently.
5. Add compatibility defaults for missing fields.

## Test plan
- Mapper unit tests (full payload, partial payload, defaults).
- Integration tests for usage/meta persistence through orchestration.

## Acceptance criteria
- Completion tokens are consistently persisted.
- Provider/model are persisted when available.
- Aggregated totals are deterministic and test-covered.

## Code snippets (illustrative)
```php
final class AgentTelemetryMapper
{
    /**
     * @return array{prompt_tokens:int,completion_tokens:int,cache_write_input_tokens:int,cache_read_input_tokens:int,reasoning_tokens:int,provider:?string,model:?string}
     */
    public function map(?Usage $usage, ?Meta $meta): array
    {
        return [
            'prompt_tokens' => $usage?->promptTokens ?? 0,
            'completion_tokens' => $usage?->completionTokens ?? 0,
            'cache_write_input_tokens' => $usage?->cacheWriteInputTokens ?? 0,
            'cache_read_input_tokens' => $usage?->cacheReadInputTokens ?? 0,
            'reasoning_tokens' => $usage?->reasoningTokens ?? 0,
            'provider' => $meta?->provider,
            'model' => $meta?->model,
        ];
    }
}
```
