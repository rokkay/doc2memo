# PRD-09: Idempotency and Uniqueness Guardrails

## Problem
Generation/regeneration flows risk duplicate side effects under retries or concurrent execution.

## Goals
- Enforce idempotent writes for retried operations.
- Prevent duplicate fan-out execution.

## Scope
- Queue fan-out points
- Section generation and metric event write paths

## Implementation plan
1. Identify duplicate-risk operations.
2. Add idempotency keys to replayable operations.
3. Add uniqueness locks/constraints where appropriate.
4. Add concurrent execution tests.

## Test plan
- Re-dispatch and concurrent collision scenarios.

## Acceptance criteria
- Repeated dispatches do not create duplicate terminal side effects.

## Code snippets (illustrative)
```php
$lock = Cache::lock("generation:{$runId}:{$sectionId}", 60);

if (! $lock->get()) {
    return;
}
```
