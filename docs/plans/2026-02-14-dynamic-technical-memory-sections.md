# Dynamic Technical Memory Sections Implementation Plan

> **For Claude:** REQUIRED SUB-SKILL: Use superpowers:executing-plans to implement this plan task-by-task.

**Goal:** Replace the static 9-section technical memory with dynamic sections generated from PCA `juicio de valor` criteria, including section scoring and weighted prioritization.

**Architecture:** Refactor the data model to represent technical memory as a parent record plus N child sections. Extract and normalize judgment criteria with explicit score points, group them by canonical section key, then generate one AI job per dynamic section. Render/export from dynamic sections only (no legacy fallback).

**Tech Stack:** Laravel 12, Livewire 4, Laravel AI SDK, Pest 4, Tailwind CSS v4

---

### Task 1: Refactor database schema for dynamic sections

**Files:**
- Create: `database/migrations/2026_02_14_000001_add_scoring_fields_to_extracted_criteria_table.php`
- Create: `database/migrations/2026_02_14_000002_refactor_technical_memories_for_dynamic_sections.php`
- Create: `database/migrations/2026_02_14_000003_create_technical_memory_sections_table.php`

**Step 1: Add criteria scoring fields**
- Add columns to `extracted_criteria`:
  - `criterion_type` enum: `judgment`, `automatic` (default `judgment`)
  - `score_points` decimal(8,2) nullable
  - `group_key` string nullable + index with `tender_id`

**Step 2: Simplify technical memories table**
- Drop static section columns from `technical_memories`:
  - `introduction`, `company_presentation`, `technical_approach`, `methodology`, `team_structure`, `timeline`, `timeline_plan`, `quality_assurance`, `risk_management`, `compliance_matrix`

**Step 3: Create dynamic sections table**
- Create `technical_memory_sections` with:
  - `id`, `technical_memory_id`, `group_key`
  - `section_number` nullable string, `section_title` string
  - `total_points` decimal(8,2) default 0
  - `weight_percent` decimal(6,2) default 0
  - `criteria_count` unsigned integer default 0
  - `sort_order` unsigned integer default 0
  - `status` enum: `pending`, `generating`, `completed`, `failed`
  - `content` longText nullable
  - `error_message` text nullable
  - timestamps
- Add indexes: `technical_memory_id + sort_order`, `technical_memory_id + status`, `group_key`

**Step 4: Run migration locally**
- Run: `php artisan migrate --no-interaction`
- Expected: all three migrations applied successfully

**Step 5: Commit**
- `git add database/migrations`
- `git commit -m "refactor: add dynamic technical memory section schema"`

---

### Task 2: Update Eloquent models and relationships

**Files:**
- Modify: `app/Models/ExtractedCriterion.php`
- Modify: `app/Models/TechnicalMemory.php`
- Create: `app/Models/TechnicalMemorySection.php`
- Modify: `app/Models/Tender.php`
- Modify: `database/factories/ExtractedCriterionFactory.php`
- Modify: `database/factories/TechnicalMemoryFactory.php`
- Create: `database/factories/TechnicalMemorySectionFactory.php`

**Step 1: Update ExtractedCriterion model**
- Add new fillable fields: `criterion_type`, `score_points`, `group_key`
- Add casts for `score_points` decimal
- Add helper scopes:
  - `scopeJudgment()`
  - `scopeAutomatic()`

**Step 2: Update TechnicalMemory model**
- Remove fillable/casts tied to dropped static fields
- Add relation `sections(): HasMany` with default order by `sort_order`

**Step 3: Add TechnicalMemorySection model**
- Add fillable and casts (`total_points`, `weight_percent`)
- Add relation `technicalMemory(): BelongsTo`

**Step 4: Add Tender convenience relation**
- Add helper relation/query usage for judgment criteria where needed

**Step 5: Update factories**
- Keep factories aligned to new schema (remove old static fields from technical memory factory)

**Step 6: Run focused tests**
- Run: `php artisan test --compact --filter=Factory`
- Expected: no schema/factory failures

**Step 7: Commit**
- `git add app/Models database/factories`
- `git commit -m "refactor: model technical memory as parent plus dynamic sections"`

---

### Task 3: Extend PCA extraction for judgment scoring

**Files:**
- Modify: `app/Ai/Agents/DocumentAnalyzer.php`
- Modify: `app/Actions/Documents/ProcessDocumentAction.php`

**Step 1: Expand structured schema in DocumentAnalyzer (PCA path)**
- Add fields per criterion:
  - `criterion_type` (`judgment` | `automatic`)
  - `score_points` (number nullable)
  - optional metadata hints for traceability

**Step 2: Persist normalized values in ProcessDocumentAction**
- Save `criterion_type`, `score_points`, `group_key`
- Build `group_key` from normalized `section_number + section_title`

**Step 3: Add fallback parser logic for score extraction**
- If `score_points` missing from AI output, parse from text/metadata patterns:
  - `X puntos`, `hasta X`, table-like rows
- Keep parser conservative and default to `null` when uncertain

**Step 4: Enforce judgment-only memory source**
- Keep automatic criteria stored, but exclude from generation payloads

**Step 5: Run targeted tests**
- Run: `php artisan test --compact --filter=ProcessDocumentAction`
- Expected: criteria persisted with normalized type and points

**Step 6: Commit**
- `git add app/Ai/Agents app/Actions/Documents`
- `git commit -m "feat: extract and normalize judgment scoring criteria"`

---

### Task 4: Replace static generation orchestration with dynamic section orchestration

**Files:**
- Modify: `app/Actions/Tenders/GenerateTechnicalMemoryAction.php`
- Modify: `app/Jobs/GenerateTechnicalMemory.php`
- Modify: `app/Jobs/GenerateTechnicalMemorySection.php`
- Create: `app/Ai/Agents/TechnicalMemoryDynamicSectionAgent.php`
- Delete: `app/Support/TechnicalMemorySections.php`
- Delete (or stop using): `app/Ai/Agents/TechnicalMemory*Agent.php` static-section classes

**Step 1: Build section groups from judgment criteria**
- Query criteria via `->judgment()`
- Group by `group_key`
- Compute per group:
  - `total_points` as sum of criterion points (null treated as 0)
  - `criteria_count`
  - `sort_order` from section number/title

**Step 2: Seed `technical_memory_sections` records**
- Reset and recreate child sections on each generation run
- Set parent memory `status = draft`

**Step 3: Dispatch one job per dynamic section**
- Pass `technicalMemorySectionId`, grouped criteria payload, tender context (PCA + PPT insights/specs)

**Step 4: Generate section content with one dynamic agent**
- Replace hardcoded field names with generic `content`
- Include section score context in prompt so generation optimizes high-value sections

**Step 5: Completion logic**
- Parent memory transitions to `generated` only when all child sections are `completed`

**Step 6: Run queue-related tests**
- Run: `php artisan test --compact --filter=GenerateTechnicalMemory`
- Run: `php artisan test --compact --filter=GenerateTechnicalMemorySection`

**Step 7: Commit**
- `git add app/Actions/Tenders app/Jobs app/Ai/Agents app/Support`
- `git commit -m "feat: generate technical memory from dynamic scored sections"`

---

### Task 5: Refactor Livewire memory view to dynamic rendering

**Files:**
- Modify: `app/Livewire/TechnicalMemories/ShowMemory.php`
- Modify: `resources/views/livewire/technical-memories/show-memory.blade.php`

**Step 1: Load dynamic sections relation**
- In component refresh/mount, eager-load `technicalMemory.sections`

**Step 2: Replace static 9-block rendering**
- Build UI from section collection with:
  - dynamic TOC
  - dynamic progress (`completed / total`)
  - section card content

**Step 3: Add scoring visibility**
- Show per section:
  - `total_points`
  - `weight_percent`
  - `criteria_count`
- Keep compliance matrix focused on `judgment` criteria

**Step 4: Preserve current UX quality**
- Keep generation polling and loading states
- Keep markdown rendering and accessibility patterns

**Step 5: Run Livewire tests**
- Run: `php artisan test --compact tests/Feature/Livewire/TechnicalMemories/ShowMemoryTest.php`

**Step 6: Commit**
- `git add app/Livewire/TechnicalMemories resources/views/livewire/technical-memories`
- `git commit -m "feat: render technical memory as dynamic scored sections in Livewire"`

---

### Task 6: Refactor Markdown/PDF export pipeline to dynamic sections

**Files:**
- Modify: `app/Http/Controllers/TechnicalMemoryController.php`
- Modify: `resources/views/technical-memories/pdf.blade.php`
- Modify: `app/Support/TechnicalMemorySections.php` (if still used as helper only) or replace with `app/Support/TechnicalMemoryMarkdownBuilder.php`

**Step 1: Build export sections from `technical_memory_sections`**
- Remove dependence on static fields
- Respect `sort_order`

**Step 2: Generate markdown dynamically**
- Include title + each section heading with generated content

**Step 3: Keep PDF output aligned**
- Preserve existing header/footer and style, swapping only section data source

**Step 4: Run export tests**
- Run: `php artisan test --compact tests/Feature/TechnicalMemoryMarkdownDownloadTest.php`
- Run: `php artisan test --compact tests/Feature/TechnicalMemoryPdfDownloadTest.php`

**Step 5: Commit**
- `git add app/Http/Controllers resources/views/technical-memories app/Support`
- `git commit -m "refactor: export markdown and pdf from dynamic memory sections"`

---

### Task 7: Rewrite feature tests around the new dynamic model

**Files:**
- Modify: `tests/Feature/Jobs/GenerateTechnicalMemoryTest.php`
- Modify: `tests/Feature/Jobs/GenerateTechnicalMemorySectionTest.php`
- Modify: `tests/Feature/Livewire/TechnicalMemories/ShowMemoryTest.php`
- Modify: `tests/Feature/TechnicalMemoryMarkdownDownloadTest.php`
- Modify: `tests/Feature/TechnicalMemoryPdfDownloadTest.php`
- Create: `tests/Feature/Actions/Documents/ProcessDocumentActionScoringTest.php`

**Step 1: Remove static-section expectations**
- Replace assertions for `9 fixed jobs/fields` with:
  - `N grouped judgment sections`
  - section points aggregation

**Step 2: Add extraction/scoring coverage**
- Verify judgment classification and points extraction from multiple input patterns

**Step 3: Add multi-licitacion robustness dataset**
- Build tests with 2-3 criterion table formats
- Verify grouping and score totals remain correct

**Step 4: Run targeted suites**
- Run: `php artisan test --compact tests/Feature/Jobs/GenerateTechnicalMemoryTest.php`
- Run: `php artisan test --compact tests/Feature/Jobs/GenerateTechnicalMemorySectionTest.php`
- Run: `php artisan test --compact tests/Feature/Livewire/TechnicalMemories/ShowMemoryTest.php`
- Run: `php artisan test --compact tests/Feature/TechnicalMemoryMarkdownDownloadTest.php`
- Run: `php artisan test --compact tests/Feature/TechnicalMemoryPdfDownloadTest.php`

**Step 5: Commit**
- `git add tests/Feature`
- `git commit -m "test: cover dynamic judgment-based technical memory generation"`

---

### Task 8: Final verification, formatting, and cleanup

**Files:**
- Modify only if needed based on lint/tests

**Step 1: Run formatter**
- Run: `vendor/bin/pint --dirty --format agent`
- Expected: clean formatting output

**Step 2: Run final focused test pack**
- Run: `php artisan test --compact --filter=TechnicalMemory`
- Run: `php artisan test --compact --filter=ProcessDocumentActionScoringTest`

**Step 3: Smoke-check app flow**
- Analyze tender docs -> generate memory -> open Livewire page -> markdown/pdf download
- Confirm no references to removed static fields remain

**Step 4: Final commit**
- `git add -A`
- `git commit -m "feat: switch technical memory to dynamic scored judgment sections"`

---

## Risks and controls

- **Risk:** AI extraction returns inconsistent criterion type/points.
  - **Control:** deterministic fallback parser + conservative null handling.
- **Risk:** section ordering breaks with unusual numbering.
  - **Control:** canonical sorting helper with numeric-aware fallback.
- **Risk:** regressions from removing static fields.
  - **Control:** broad test updates + grep check for old field usage.

## Definition of done

- Technical memory generation uses only `judgment` criteria.
- Number of generated sections is fully dynamic (`N` from PCA grouping).
- Section score = sum of criterion points inside each group.
- Livewire view and exports are dynamic and score-aware.
- All affected tests pass and code is Pint-formatted.
