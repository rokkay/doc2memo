# ProcessDocumentAction Decomposition Design

Date: 2026-03-03
PRD: `docs/plans/2026-03-01-refactor-prds/PRD-06-process-document-action-decomposition.md`
Decision: Option 1 (Vertical workflow split)

## Goal

Refactor `ProcessDocumentAction` into a thin orchestrator that delegates extraction, persistence, and status transitions to focused invokable actions while preserving behavior and enabling small, test-backed edge-case fixes.

## Architecture

`ProcessDocumentAction` remains the single entrypoint for document processing and keeps the workflow order unchanged:

1. Mark document as processing.
2. Extract text from file.
3. Run AI analysis and metrics collection.
4. Persist extracted domain data in a transaction.
5. Persist AI cost entries.
6. Finalize document and tender statuses.

Responsibilities currently implemented inline are extracted into dedicated actions under `app/Actions/Documents/`.

## New Action Boundaries

- `ExtractDocumentTextAction`
  - Reads markdown/text directly.
  - Parses PDFs through `Smalot\PdfParser\Parser`.

- `PersistDocumentAnalysisAction`
  - Owns transaction and delegates to specialized persistence actions.
  - Receives `$analysis`, `$costSummary`, and `$dedicatedCriteria`.

- `PersistPcaExtractionAction`
  - Persists tender info and PCA criteria.
  - Keeps criterion normalization, score extraction, source-reference resolution, group key, and judgment subcriteria expansion.

- `PersistPptExtractionAction`
  - Persists extracted technical specifications for PPT documents.

- `PersistInsightsAction`
  - Persists `DocumentInsight` rows and returns inserted count.

- `PersistAiCostEntriesAction`
  - Persists `AiCostEntry` rows from analyzer breakdown.

- `RefreshTenderStatusAction`
  - Computes tender aggregate status from document statuses.

## Data Flow

- No route/job contract changes.
- `App\Jobs\ProcessDocument` keeps calling `ProcessDocumentAction`.
- Existing analysis payload contract from `AnalyzeDocumentWithMetricsAction` remains unchanged.

## Edge Cases To Protect With Tests

- Text extraction for `md` and `txt` files vs PDF parser path.
- Missing or malformed `costSummary.breakdown` payload.
- Score extraction precedence: explicit score -> metadata score -> regex from description.
- Judgment subcriteria expansion only when numbering is not explicit.
- Known legal-compliance section forcing `criterion_type=automatic`.
- Tender status refresh precedence: `failed` over `analyzing` over `completed`.

## Testing Strategy

- Keep orchestrator regression coverage in feature tests.
- Add focused unit tests for extracted actions with parsing/normalization logic.
- Add behavior-preserving assertions first, then edge-case assertions.

## Non-Goals

- No schema changes.
- No queue flow changes.
- No API or Livewire contract changes.
