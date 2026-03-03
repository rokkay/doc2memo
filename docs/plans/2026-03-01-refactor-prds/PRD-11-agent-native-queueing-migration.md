# PRD-11: Agent Native Queueing Migration

## Problem
Some AI flows are wrapped in custom Laravel jobs primarily to execute agent prompts, adding extra queue boilerplate.

## Goals
- Migrate eligible AI prompt flows to agent-native `queue()`.
- Preserve existing success/failure side effects.

## Scope
- AI prompt orchestration paths currently using wrapper jobs.

## Reference
- Laravel AI SDK queueing: `https://laravel.com/docs/12.x/ai-sdk#queueing`

## Implementation plan
1. Inventory wrapper jobs and classify migration candidates.
2. Replace wrapper dispatch with `agent->queue($prompt)` where safe.
3. Move side effects into thin `then()` / `catch()` delegators calling domain actions.
4. Add queued-prompt assertions and callback side-effect tests.

## Test plan
- `Agent::assertQueued(...)` for migrated flows.
- Success and failure callback behavior parity tests.

## Acceptance criteria
- At least one core flow migrated with no behavior regression.

## Code snippets (illustrative)
```php
$agent->queue($prompt)
    ->then(fn (AgentResponse $response) => app(ApplyGeneratedSectionResponseAction::class)->handle($section, $response, $runId))
    ->catch(fn (Throwable $e) => app(HandleSectionGenerationFailureAction::class)->handle($section, $e, $runId));
```
