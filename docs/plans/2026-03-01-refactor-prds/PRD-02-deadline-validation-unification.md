# PRD-02: Deadline Validation Unification

## Problem
Deadline validation differs between Livewire flows and controller/request flows.

## Goals
- Define one canonical deadline contract.
- Ensure all entrypoints enforce identical rules.

## Scope
- Livewire tender creation/update validation
- Form Request validation paths

## Implementation plan
1. Define canonical deadline format and requiredness.
2. Centralize validation in one reusable request/data rule definition.
3. Update Livewire and HTTP entrypoints to use the same rule source.
4. Add parity tests.

## Test plan
- Valid date, invalid format, empty value, edge boundaries.
- Cross-entrypoint parity tests.

## Acceptance criteria
- No deadline rule drift across entrypoints.
- Shared validation behavior is covered by tests.

## Code snippets (illustrative)
```php
public function rules(): array
{
    return [
        'deadline' => ['required', 'date_format:Y-m-d'],
    ];
}
```
