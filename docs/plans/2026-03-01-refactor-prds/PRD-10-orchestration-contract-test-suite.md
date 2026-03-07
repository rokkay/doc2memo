# PRD-10: Orchestration Contract Test Suite

## Problem
Critical orchestration invariants are distributed across tests, not captured as one explicit contract suite.

## Goals
- Define invariants as first-class contract tests.
- Protect refactors with high-signal integration assertions.

## Scope
- New contract tests under `tests/Feature/Contracts`.

## Contract invariants
1. One run ID propagated across section jobs in one generation run.
2. Memory reaches `generated` only when blocking statuses are absent.
3. Retry behavior is bounded and attempt-aware.
4. Summary counts match terminal event stream.

## Implementation plan
1. Create contract test directory and naming convention.
2. Create shared builders/helpers for orchestration fixtures.
3. Add invariant test files.

## Test execution
- `php artisan test --compact tests/Feature/Contracts`

## Acceptance criteria
- Contract suite passes and becomes part of CI.

## Code snippets (illustrative)
```php
it('propagates one run id to every section job', function (): void {
    Bus::fake();

    app(GenerateTechnicalMemoryAction::class)->handle($tender);

    Bus::assertBatched(function ($batch): bool {
        return collect($batch->jobs)->map(fn ($job) => $job->runId)->unique()->count() === 1;
    });
});
```
