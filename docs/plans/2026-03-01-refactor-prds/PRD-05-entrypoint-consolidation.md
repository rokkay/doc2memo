# PRD-05: Entrypoint Consolidation

## Problem
Dual entrypoints (legacy controllers + Livewire) may diverge in behavior.

## Goals
- Consolidate orchestration logic behind one canonical domain action layer.
- Preserve route compatibility while reducing duplicated behavior.

## Scope
- Controller and Livewire entrypoints that trigger tender analysis/memory generation.

## Implementation plan
1. Audit behavior parity between entrypoints.
2. Move shared orchestration to dedicated action(s).
3. Make both entrypoints call shared actions.
4. Add parity tests.

## Test plan
- Entry-point A vs B produces same state transitions and side effects.

## Acceptance criteria
- Domain behavior is centralized and parity-tested.

## Code snippets (illustrative)
```php
app(AnalyzeTenderDocumentsAction::class)->handle($tender);
```
