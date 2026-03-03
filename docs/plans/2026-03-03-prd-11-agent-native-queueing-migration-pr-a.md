# PRD-11 Agent Native Queueing Migration (PR A) Implementation Plan

> **For Claude:** REQUIRED SUB-SKILL: Use superpowers:executing-plans to implement this plan task-by-task.

**Goal:** Migrate the document-analysis wrapper job flow from `ProcessDocument::dispatch(...)` to Laravel AI SDK native agent queueing while preserving all success/failure side effects.

**Architecture:** Keep document-domain logic in actions, remove job-wrapper orchestration from dispatch paths, and use `DocumentAnalyzer->queue($prompt)->then(...)->catch(...)` with thin callback delegators. Persist side effects through focused actions that receive scalar IDs and payload arrays to keep callbacks serializable and deterministic.

**Tech Stack:** Laravel 12, PHP 8.5, laravel/ai 0.2.x, Pest 4, Laravel Queue.

---

### Task 1: Add Queueing Orchestrator Action (No Call Site Changes Yet)

**Files:**
- Create: `app/Actions/Documents/QueueDocumentAnalysisAction.php`
- Test: `tests/Feature/Actions/Documents/QueueDocumentAnalysisActionTest.php`

**Step 1: Write the failing test**

Create a new feature test that verifies native queueing is invoked and no wrapper job dispatch is required:

```php
it('queues document analyzer prompt with callback wiring', function (): void {
    // Arrange: document in uploaded state and analyzer fake
    // Act: (new QueueDocumentAnalysisAction)($document)
    // Assert: DocumentAnalyzer::assertQueued(...) and status changes to processing
});
```

**Step 2: Run test to verify it fails**

Run: `php artisan test --compact tests/Feature/Actions/Documents/QueueDocumentAnalysisActionTest.php`
Expected: FAIL because action does not exist.

**Step 3: Write minimal implementation**

Implement `QueueDocumentAnalysisAction::__invoke(Document $document): void`:
- guard valid statuses (`uploaded`, `failed`)
- set `status=processing`, clear `processing_error`
- load/extract document text through existing action boundary
- call `DocumentAnalyzer->queue($text)->then(...)->catch(...)`

**Step 4: Run test to verify it passes**

Run: `php artisan test --compact tests/Feature/Actions/Documents/QueueDocumentAnalysisActionTest.php`
Expected: PASS.

**Step 5: Commit**

```bash
git add app/Actions/Documents/QueueDocumentAnalysisAction.php tests/Feature/Actions/Documents/QueueDocumentAnalysisActionTest.php
git commit -m "feat: add native queued analyzer orchestration action"
```

### Task 2: Add Thin Success and Failure Callback Delegators

**Files:**
- Create: `app/Actions/Documents/ApplyQueuedDocumentAnalysisSuccessAction.php`
- Create: `app/Actions/Documents/ApplyQueuedDocumentAnalysisFailureAction.php`
- Modify: `tests/Feature/Jobs/ProcessDocumentTest.php`
- Test: `tests/Feature/Jobs/ProcessDocumentTest.php`

**Step 1: Write failing parity tests**

Add tests for callback parity:
- success callback persists extracted rows, costs, and sets `document.status=analyzed`
- failure callback sets `document.status=failed`, stores error fields, and updates tender status precedence

```php
it('applies queued analyzer success side effects with parity', function (): void {
    // Arrange: queued response payload
    // Act: success action
    // Assert: same DB side effects as previous job flow
});

it('applies queued analyzer failure side effects with parity', function (): void {
    // Arrange: throwable-like failure
    // Act: failure action
    // Assert: failed states + error persistence
});
```

**Step 2: Run test to verify it fails**

Run: `php artisan test --compact tests/Feature/Jobs/ProcessDocumentTest.php --filter="queued analyzer"`
Expected: FAIL.

**Step 3: Write minimal implementation**

Implement both actions as thin delegators:
- success action delegates to existing persistence path (via existing actions)
- failure action mirrors `ProcessDocument` catch behavior exactly

**Step 4: Run tests to verify pass**

Run: `php artisan test --compact tests/Feature/Jobs/ProcessDocumentTest.php --filter="queued analyzer"`
Expected: PASS.

**Step 5: Commit**

```bash
git add app/Actions/Documents/ApplyQueuedDocumentAnalysisSuccessAction.php app/Actions/Documents/ApplyQueuedDocumentAnalysisFailureAction.php tests/Feature/Jobs/ProcessDocumentTest.php
git commit -m "test: add queued analyzer callback parity coverage"
```

### Task 3: Switch Dispatch Call Sites to Native Queueing Action

**Files:**
- Modify: `app/Actions/Tenders/AnalyzeTenderDocumentsAction.php`
- Modify: `app/Services/DocumentAnalysisService.php`
- Modify: `app/Http/Controllers/TenderController.php`
- Modify: `tests/Feature/Livewire/Tenders/CreateTenderTest.php`
- Test: `tests/Feature/Livewire/Tenders/CreateTenderTest.php`

**Step 1: Write failing call-site test adjustments**

Update dispatch-oriented assertions to AI queued assertions:

```php
DocumentAnalyzer::assertQueued(/* expected count or predicate */);
```

and remove expectations tied to `ProcessDocument::class` push semantics.

**Step 2: Run test to verify it fails**

Run: `php artisan test --compact tests/Feature/Livewire/Tenders/CreateTenderTest.php`
Expected: FAIL before call-site code updates.

**Step 3: Write minimal implementation**

Replace `ProcessDocument::dispatch($document)` with `QueueDocumentAnalysisAction` invocation in all three call sites.

**Step 4: Run tests to verify pass**

Run:
- `php artisan test --compact tests/Feature/Livewire/Tenders/CreateTenderTest.php`
- `php artisan test --compact tests/Feature/Jobs/ProcessDocumentTest.php`
Expected: PASS.

**Step 5: Commit**

```bash
git add app/Actions/Tenders/AnalyzeTenderDocumentsAction.php app/Services/DocumentAnalysisService.php app/Http/Controllers/TenderController.php tests/Feature/Livewire/Tenders/CreateTenderTest.php
git commit -m "refactor: route document analysis dispatch through agent native queueing"
```

### Task 4: Deprecate Wrapper Job Usage in PR A Scope

**Files:**
- Modify: `app/Jobs/ProcessDocument.php`
- Modify: `tests/Feature/Jobs/ProcessDocumentTest.php`
- Test: `tests/Feature/Jobs/ProcessDocumentTest.php`

**Step 1: Write failing test for wrapper deprecation contract**

Add test asserting the flow no longer depends on wrapper job dispatch from primary entrypoints.

```php
it('does not require process document wrapper dispatch in main flows', function (): void {
    // Assert native queue assertions pass without Queue::assertPushed(ProcessDocument::class)
});
```

**Step 2: Run test to verify it fails**

Run: `php artisan test --compact tests/Feature/Jobs/ProcessDocumentTest.php --filter="wrapper dispatch"`
Expected: FAIL.

**Step 3: Write minimal implementation**

Either:
- keep `ProcessDocument` as compatibility shim for now (non-primary path), or
- remove wrapper if no runtime references remain.

For PR A, prefer compatibility shim to reduce risk.

**Step 4: Run test to verify pass**

Run: `php artisan test --compact tests/Feature/Jobs/ProcessDocumentTest.php`
Expected: PASS.

**Step 5: Commit**

```bash
git add app/Jobs/ProcessDocument.php tests/Feature/Jobs/ProcessDocumentTest.php
git commit -m "refactor: de-emphasize process document wrapper in favor of native queue flow"
```

### Task 5: Final Verification and Formatting

**Files:**
- Modify (if needed): changed PHP/test files from Tasks 1-4

**Step 1: Run formatter**

Run: `vendor/bin/pint --dirty --format agent`
Expected: style fixes applied.

**Step 2: Run focused regression suite**

Run:
- `php artisan test --compact tests/Feature/Actions/Documents/QueueDocumentAnalysisActionTest.php`
- `php artisan test --compact tests/Feature/Jobs/ProcessDocumentTest.php`
- `php artisan test --compact tests/Feature/Livewire/Tenders/CreateTenderTest.php`

Expected: PASS.

**Step 3: Optional confidence run**

Run: `php artisan test --compact --filter="Tender|ProcessDocument|DocumentAnalysis"`
Expected: PASS.

**Step 4: Commit final polish**

```bash
git add app tests
git commit -m "test: verify native queued analyzer migration parity"
```

## Required Skills During Execution

- `@superpowers/test-driven-development`
- `@developing-with-ai-sdk`
- `@laravel-actions`
- `@pest-testing`
- `@superpowers/verification-before-completion`
