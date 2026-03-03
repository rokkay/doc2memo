# PRD-07: Status Transition Policy Centralization

## Problem
Tender and technical-memory status transitions are spread across jobs/actions/listeners, making invariants hard to enforce.

## Goals
- Centralize legal state transitions.
- Fail fast on invalid transitions.

## Scope
- Status writes for tender/memory/section state machines.

## Implementation plan
1. Define legal transition matrix.
2. Implement centralized transition policy/service.
3. Replace ad-hoc status writes with policy calls.
4. Add transition tests (valid/invalid).

## Test plan
- Matrix tests for legal transitions.
- Invalid transitions produce expected errors.

## Acceptance criteria
- No direct transition writes bypass policy in target scope.

## Code snippets (illustrative)
```php
$this->statusPolicy->transition(
    model: $memory,
    to: TechnicalMemoryStatus::Generated,
);
```
