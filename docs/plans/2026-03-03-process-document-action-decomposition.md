# ProcessDocumentAction Decomposition Implementation Plan

> **For Claude:** REQUIRED SUB-SKILL: Use superpowers:executing-plans to implement this plan task-by-task.

**Goal:** Decompose `ProcessDocumentAction` into focused invokable actions while preserving behavior and adding TDD coverage for edge cases.

**Architecture:** Keep `ProcessDocumentAction` as the orchestrator and delegate extraction, persistence, and status refresh to focused actions in `app/Actions/Documents`. Preserve the existing workflow and payload contracts from `AnalyzeDocumentWithMetricsAction`. Use TDD for each extraction boundary and high-risk parsing/normalization edge case.

**Tech Stack:** Laravel 12, PHP 8.5, Pest 4, Eloquent ORM, Laravel actions pattern.

---

### Task 1: Lock Current Orchestrator Behavior (Regression Net)

**Files:**
- Modify: `tests/Feature/Actions/Documents/ProcessDocumentActionScoringTest.php`
- Test: `tests/Feature/Actions/Documents/ProcessDocumentActionScoringTest.php`

**Step 1: Write the failing test**

Add one explicit orchestrator-focused regression test that verifies the full happy-path sequence result (status, insights count, AI cost rows, extracted criteria/spec data depending on type).

```php
it('keeps process document orchestration behavior stable', function (): void {
    // Arrange: fake storage + analyzer payload
    // Act: (new ProcessDocumentAction)($document)
    // Assert: document/tender statuses and persisted rows are unchanged
});
```

**Step 2: Run test to verify it fails**

Run: `php artisan test --compact tests/Feature/Actions/Documents/ProcessDocumentActionScoringTest.php --filter="orchestration behavior stable"`
Expected: FAIL because assertions are not implemented yet.

**Step 3: Add/adjust only minimal assertions to match existing behavior**

Implement assertions without changing production code.

**Step 4: Run test to verify it passes**

Run: `php artisan test --compact tests/Feature/Actions/Documents/ProcessDocumentActionScoringTest.php --filter="orchestration behavior stable"`
Expected: PASS.

**Step 5: Commit**

```bash
git add tests/Feature/Actions/Documents/ProcessDocumentActionScoringTest.php
git commit -m "test: add orchestrator regression guard for document processing"
```

### Task 2: Extract Text Reader Action

**Files:**
- Create: `app/Actions/Documents/ExtractDocumentTextAction.php`
- Modify: `app/Actions/Documents/ProcessDocumentAction.php`
- Create: `tests/Unit/Actions/Documents/ExtractDocumentTextActionTest.php`
- Test: `tests/Unit/Actions/Documents/ExtractDocumentTextActionTest.php`

**Step 1: Write the failing test**

Cover edge cases:
- reads markdown/text files from storage path
- delegates PDF parsing path for `.pdf`

```php
it('reads markdown and text files directly', function (): void {
    // Arrange a fake local file
    // Act invoke ExtractDocumentTextAction
    // Assert exact content
});

it('parses pdf files through parser path', function (): void {
    // Arrange PDF fixture
    // Act invoke action
    // Assert extracted text is string (non-empty for known fixture)
});
```

**Step 2: Run test to verify it fails**

Run: `php artisan test --compact tests/Unit/Actions/Documents/ExtractDocumentTextActionTest.php`
Expected: FAIL because action does not exist.

**Step 3: Write minimal implementation**

Implement `__invoke(Document $document): string` with current `extractText` logic from `ProcessDocumentAction`.

**Step 4: Wire orchestrator**

Inject or resolve `ExtractDocumentTextAction` in `ProcessDocumentAction` and replace inline call.

**Step 5: Run tests to verify pass**

Run:
- `php artisan test --compact tests/Unit/Actions/Documents/ExtractDocumentTextActionTest.php`
- `php artisan test --compact tests/Feature/Actions/Documents/ProcessDocumentActionScoringTest.php --filter="orchestration behavior stable"`
Expected: PASS.

**Step 6: Commit**

```bash
git add app/Actions/Documents/ExtractDocumentTextAction.php app/Actions/Documents/ProcessDocumentAction.php tests/Unit/Actions/Documents/ExtractDocumentTextActionTest.php
git commit -m "refactor: extract document text reading into dedicated action"
```

### Task 3: Extract AI Cost Persistence Action

**Files:**
- Create: `app/Actions/Documents/PersistAiCostEntriesAction.php`
- Modify: `app/Actions/Documents/ProcessDocumentAction.php`
- Create: `tests/Unit/Actions/Documents/PersistAiCostEntriesActionTest.php`
- Test: `tests/Unit/Actions/Documents/PersistAiCostEntriesActionTest.php`

**Step 1: Write the failing test**

Cover edge cases:
- persists rows for analyzer and dedicated extractor categories
- handles missing/malformed breakdown entries without errors
- defaults token usage values to zero and `available=false`

```php
it('persists analyzer and dedicated ai cost rows', function (): void {
    // Arrange document + complete breakdown
    // Act PersistAiCostEntriesAction
    // Assert ai_cost_entries rows
});

it('skips malformed breakdown payload safely', function (): void {
    // Arrange malformed breakdown
    // Act
    // Assert no unexpected rows/errors
});
```

**Step 2: Run test to verify it fails**

Run: `php artisan test --compact tests/Unit/Actions/Documents/PersistAiCostEntriesActionTest.php`
Expected: FAIL.

**Step 3: Write minimal implementation**

Move current `storeAiCostEntries` logic into action `__invoke(Document $document, array $breakdown): void`.

**Step 4: Wire orchestrator**

Replace direct method call with action invocation.

**Step 5: Run tests to verify pass**

Run:
- `php artisan test --compact tests/Unit/Actions/Documents/PersistAiCostEntriesActionTest.php`
- `php artisan test --compact tests/Feature/Actions/Documents/ProcessDocumentActionScoringTest.php --filter="stores criterion type"`
Expected: PASS.

**Step 6: Commit**

```bash
git add app/Actions/Documents/PersistAiCostEntriesAction.php app/Actions/Documents/ProcessDocumentAction.php tests/Unit/Actions/Documents/PersistAiCostEntriesActionTest.php
git commit -m "refactor: extract ai cost entry persistence action"
```

### Task 4: Extract PCA Persistence Action

**Files:**
- Create: `app/Actions/Documents/PersistPcaExtractionAction.php`
- Modify: `app/Actions/Documents/ProcessDocumentAction.php`
- Create: `tests/Unit/Actions/Documents/PersistPcaExtractionActionTest.php`
- Test: `tests/Unit/Actions/Documents/PersistPcaExtractionActionTest.php`

**Step 1: Write the failing test**

Cover critical edge cases already validated at feature level:
- criterion type normalization for legal compliance sections
- score extraction fallback from metadata/description
- grouped judgment expansion only when numbering is absent
- dedicated extractor criteria upsert by `(document_id, criterion_type, group_key)`

```php
it('normalizes legal compliance criteria as automatic', function (): void {
    // Arrange PCA analysis payload
    // Act PersistPcaExtractionAction
    // Assert criterion_type automatic
});

it('extracts score points using fallback precedence', function (): void {
    // Arrange score points missing in primary field
    // Act
    // Assert derived score points
});
```

**Step 2: Run test to verify it fails**

Run: `php artisan test --compact tests/Unit/Actions/Documents/PersistPcaExtractionActionTest.php`
Expected: FAIL.

**Step 3: Write minimal implementation**

Move `storePcaData` and supporting helper methods into `PersistPcaExtractionAction`.

**Step 4: Wire orchestrator**

`ProcessDocumentAction` delegates PCA branch persistence to the new action.

**Step 5: Run tests to verify pass**

Run:
- `php artisan test --compact tests/Unit/Actions/Documents/PersistPcaExtractionActionTest.php`
- `php artisan test --compact tests/Feature/Actions/Documents/ProcessDocumentActionScoringTest.php`
Expected: PASS.

**Step 6: Commit**

```bash
git add app/Actions/Documents/PersistPcaExtractionAction.php app/Actions/Documents/ProcessDocumentAction.php tests/Unit/Actions/Documents/PersistPcaExtractionActionTest.php tests/Feature/Actions/Documents/ProcessDocumentActionScoringTest.php
git commit -m "refactor: extract pca persistence and criterion normalization logic"
```

### Task 5: Extract PPT + Insights + Tender Status Actions

**Files:**
- Create: `app/Actions/Documents/PersistPptExtractionAction.php`
- Create: `app/Actions/Documents/PersistInsightsAction.php`
- Create: `app/Actions/Documents/RefreshTenderStatusAction.php`
- Modify: `app/Actions/Documents/ProcessDocumentAction.php`
- Create: `tests/Unit/Actions/Documents/PersistInsightsActionTest.php`
- Create: `tests/Unit/Actions/Documents/RefreshTenderStatusActionTest.php`
- Test: `tests/Unit/Actions/Documents/PersistInsightsActionTest.php`
- Test: `tests/Unit/Actions/Documents/RefreshTenderStatusActionTest.php`

**Step 1: Write failing unit tests**

Cover edge cases:
- insights count return value equals inserted rows
- tender status precedence: failed > analyzing > completed

```php
it('returns inserted insights count', function (): void {
    // Arrange insights payload
    // Act PersistInsightsAction
    // Assert returned count and rows
});

it('prioritizes failed status when any document failed', function (): void {
    // Arrange tender documents with mixed states
    // Act RefreshTenderStatusAction
    // Assert tender status is failed
});
```

**Step 2: Run tests to verify fail**

Run:
- `php artisan test --compact tests/Unit/Actions/Documents/PersistInsightsActionTest.php`
- `php artisan test --compact tests/Unit/Actions/Documents/RefreshTenderStatusActionTest.php`
Expected: FAIL.

**Step 3: Write minimal implementations**

Move logic from `storePptData`, `storeInsights`, and `refreshTenderStatus` into dedicated actions.

**Step 4: Wire orchestrator**

Delegate PPT branch, insights persistence, and tender status refresh through extracted actions.

**Step 5: Run tests to verify pass**

Run:
- `php artisan test --compact tests/Unit/Actions/Documents/PersistInsightsActionTest.php`
- `php artisan test --compact tests/Unit/Actions/Documents/RefreshTenderStatusActionTest.php`
- `php artisan test --compact tests/Feature/Actions/Documents/ProcessDocumentActionScoringTest.php`
Expected: PASS.

**Step 6: Commit**

```bash
git add app/Actions/Documents/PersistPptExtractionAction.php app/Actions/Documents/PersistInsightsAction.php app/Actions/Documents/RefreshTenderStatusAction.php app/Actions/Documents/ProcessDocumentAction.php tests/Unit/Actions/Documents/PersistInsightsActionTest.php tests/Unit/Actions/Documents/RefreshTenderStatusActionTest.php
git commit -m "refactor: delegate ppt persistence, insights, and tender status updates to actions"
```

### Task 6: Add Transactional Persistence Orchestrator Action

**Files:**
- Create: `app/Actions/Documents/PersistDocumentAnalysisAction.php`
- Modify: `app/Actions/Documents/ProcessDocumentAction.php`
- Create: `tests/Feature/Actions/Documents/ProcessDocumentActionOrchestrationTest.php`
- Test: `tests/Feature/Actions/Documents/ProcessDocumentActionOrchestrationTest.php`

**Step 1: Write failing orchestrator test**

Validate that the parent action delegates and still commits equivalent side effects for PCA and PPT branches.

```php
it('delegates persistence through transaction action and keeps outcomes', function (): void {
    // Arrange document + fake analyzer payload
    // Act ProcessDocumentAction
    // Assert side effects and statuses
});
```

**Step 2: Run test to verify fail**

Run: `php artisan test --compact tests/Feature/Actions/Documents/ProcessDocumentActionOrchestrationTest.php`
Expected: FAIL.

**Step 3: Write minimal implementation**

`PersistDocumentAnalysisAction::__invoke(...)` wraps transaction and delegates to:
- `PersistPcaExtractionAction` (PCA only)
- `PersistPptExtractionAction` (PPT only)
- `PersistInsightsAction`
- `PersistAiCostEntriesAction`
- `RefreshTenderStatusAction`

**Step 4: Simplify ProcessDocumentAction**

Keep only orchestration logic and state updates. Remove extracted private methods.

**Step 5: Run tests to verify pass**

Run:
- `php artisan test --compact tests/Feature/Actions/Documents/ProcessDocumentActionOrchestrationTest.php`
- `php artisan test --compact tests/Feature/Actions/Documents/ProcessDocumentActionScoringTest.php`
Expected: PASS.

**Step 6: Commit**

```bash
git add app/Actions/Documents/PersistDocumentAnalysisAction.php app/Actions/Documents/ProcessDocumentAction.php tests/Feature/Actions/Documents/ProcessDocumentActionOrchestrationTest.php
git commit -m "refactor: keep process document action as thin orchestrator"
```

### Task 7: Final Verification and Quality

**Files:**
- Modify (if formatting fixes): `app/Actions/Documents/*.php`
- Modify (if assertion polish): `tests/Unit/Actions/Documents/*.php`, `tests/Feature/Actions/Documents/*.php`

**Step 1: Run formatter**

Run: `vendor/bin/pint --dirty --format agent`
Expected: code style fixed if needed.

**Step 2: Run targeted tests**

Run:
- `php artisan test --compact tests/Unit/Actions/Documents/ExtractDocumentTextActionTest.php`
- `php artisan test --compact tests/Unit/Actions/Documents/PersistAiCostEntriesActionTest.php`
- `php artisan test --compact tests/Unit/Actions/Documents/PersistPcaExtractionActionTest.php`
- `php artisan test --compact tests/Unit/Actions/Documents/PersistInsightsActionTest.php`
- `php artisan test --compact tests/Unit/Actions/Documents/RefreshTenderStatusActionTest.php`
- `php artisan test --compact tests/Feature/Actions/Documents/ProcessDocumentActionOrchestrationTest.php`
- `php artisan test --compact tests/Feature/Actions/Documents/ProcessDocumentActionScoringTest.php`
Expected: PASS.

**Step 3: Commit final polish**

```bash
git add app/Actions/Documents tests/Unit/Actions/Documents tests/Feature/Actions/Documents
git commit -m "test: add edge-case coverage for process document decomposition"
```

## Required Skills During Execution

- `@superpowers/test-driven-development` for red-green-refactor discipline.
- `@laravel-actions` for action boundaries and composition.
- `@pest-testing` for assertion and dataset patterns.
- `@superpowers/verification-before-completion` before claiming success.
