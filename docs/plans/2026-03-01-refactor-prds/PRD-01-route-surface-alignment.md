# PRD-01: Route Surface Alignment

## Problem
`Route::resource('tenders', ...)` exposes actions that are not fully implemented in `TenderController`, creating endpoint drift.

## Goals
- Align declared routes with implemented controller methods.
- Prevent accidental exposure of unsupported actions.

## Scope
- `routes/web.php`
- Tender route feature tests

## Implementation plan
1. Audit tender resource routes vs implemented controller methods.
2. Restrict resource routes with `only()` / `except()` to the supported surface.
3. Add tests for allowed endpoints and blocked endpoints.

## Test plan
- Run targeted feature tests for tender routes.

## Acceptance criteria
- No route exists for unimplemented tender controller actions.
- Route behavior is explicitly tested.

## Code snippets (illustrative)
```php
Route::resource('tenders', TenderController::class)
    ->only(['index', 'create', 'store', 'show']);
```
