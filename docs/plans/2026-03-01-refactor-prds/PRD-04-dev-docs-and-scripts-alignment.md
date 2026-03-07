# PRD-04: Dev Docs and Scripts Alignment

## Problem
`README.md` and `composer.json` scripts have drifted, causing onboarding and runtime confusion.

## Goals
- Make docs match executable scripts.
- Define one canonical local-dev workflow.

## Scope
- `README.md`
- `composer.json` script references and usage docs

## Implementation plan
1. Compare documented commands with actual scripts.
2. Update docs to canonical startup/test/worker commands.
3. Add verification checklist for first-time setup.

## Test plan
- Execute documented commands in clean setup.

## Acceptance criteria
- All documented commands run as written.
- No conflicting instructions remain.

## Code snippets (illustrative)
```md
## Local Development

1. `composer install`
2. `npm install`
3. `composer run dev`
```
