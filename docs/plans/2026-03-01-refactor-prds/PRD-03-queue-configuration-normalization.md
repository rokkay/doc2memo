# PRD-03: Queue Configuration Normalization

## Problem
Queue connection and queue-name resolution for technical memory generation are inconsistent and partially hardcoded.

## Goals
- Backend-agnostic queue resolution.
- Deterministic defaults with optional feature overrides.

## Scope
- `config/technical_memory.php`
- Generation queue resolution in orchestration actions

## Implementation plan
1. Add `technical_memory.queue.connection` and `technical_memory.queue.name`.
2. Create a queue resolver class.
3. Use resolver in generation dispatch paths.
4. Add configuration matrix tests.

## Test plan
- Redis/default and non-redis/default scenarios.
- Explicit override scenarios.

## Acceptance criteria
- No hardcoded Redis config path in generation queue logic.
- Queue selection behavior is tested.

## Code snippets (illustrative)
```php
$connection = config('technical_memory.queue.connection') ?? config('queue.default');
$queue = config('technical_memory.queue.name') ?? config("queue.connections.{$connection}.queue", 'default');
```
