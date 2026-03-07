# PRD-06: ProcessDocumentAction Decomposition

## Problem
`ProcessDocumentAction` has multiple responsibilities (parse, classify, persist, dispatch, metrics), increasing complexity and coupling.

## Goals
- Split responsibilities into focused invokable actions.
- Keep orchestration action thin and readable.

## Scope
- `app/Actions/Documents/ProcessDocumentAction.php`
- New extracted actions/services

## Implementation plan
1. Identify clear sub-responsibility boundaries.
2. Extract small invokable classes.
3. Keep parent action as orchestrator only.
4. Add unit tests for extracted units and integration test for orchestrator.

## Test plan
- Unit tests per extracted class.
- Orchestrator regression test.

## Acceptance criteria
- `ProcessDocumentAction` delegates rather than implementing all details inline.

## Code snippets (illustrative)
```php
final class ProcessDocumentAction
{
    public function handle(Document $document): void
    {
        $parsed = $this->parseDocument->handle($document);
        $analysis = $this->analyzeDocument->handle($parsed);
        $this->persistAnalysis->handle($document, $analysis);
    }
}
```
