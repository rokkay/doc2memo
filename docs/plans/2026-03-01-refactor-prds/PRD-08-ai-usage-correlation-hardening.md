# PRD-08: AI Usage Correlation Hardening

## Problem
AI usage attribution can become ordering-sensitive when events arrive out of order or retried.

## Goals
- Make attribution deterministic.
- Preserve correctness under retries and duplicate delivery.

## Scope
- AI usage listener(s), correlation key generation, event persistence.

## Implementation plan
1. Define deterministic correlation key.
2. Use key across prompt-response-event lifecycle.
3. Add idempotent upsert semantics for usage events.
4. Add out-of-order and duplicate-event tests.

## Test plan
- Normal ordering, reordered, duplicated events.

## Acceptance criteria
- Usage attribution remains correct under retries and reorder scenarios.

## Code snippets (illustrative)
```php
$correlationKey = sprintf('%s:%s:%s', $runId, $sectionId, $attempt);
```
