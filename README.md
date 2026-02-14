# Doc2Memo

Doc2Memo helps teams build stronger technical memories for public tenders in Spain.
Starting from the tender documents (`PCA` and `PPT`), it analyzes requirements with AI and produces a structured, editable, and exportable technical proposal.

> From raw tender docs to a submission-ready technical memory in minutes, not days.

Sample documents: `docs/samples/*`.

## For teams

Doc2Memo is designed to reduce time spent on repetitive drafting while keeping proposal quality high.

- Upload your `PCA` and `PPT` files (`pdf`, `md`, `txt`).
- Let asynchronous AI analysis extract criteria, specifications, and strategic insights.
- Generate a technical memory in dynamic sections aligned with judgment-based evaluation criteria.
- Regenerate individual sections when you want a better draft.
- Export the final output as `PDF` or `Markdown`.

### Quick 5-minute flow

1. Create a tender and upload `PCA` + `PPT`.
2. Wait for background analysis to complete.
3. Generate the technical memory.
4. Review sections, regenerate weak parts, and export.

### Why it is useful

- Prioritizes scoring opportunities from judgment criteria.
- Speeds up first drafts without losing structure.
- Keeps the process traceable with operational metrics.

## Key capabilities

- Tender creation and document ingestion for `PCA`/`PPT`.
- Queue-based analysis pipeline with AI agents.
- Criteria classification (`judgment` and `automatic`) with score normalization.
- Dynamic section generation (one queue job per section).
- Per-section quality gate, retries, and optional style editor pass.
- Internal operational metrics dashboard at `technical-memories/operational-metrics`.

### Output quality controls

- Dynamic sections are built from judgment-based criteria groups.
- Each section runs through a quality gate before completion.
- Failed quality checks can be retried automatically.
- Optional style editing improves readability while preserving facts.

## Architecture at a glance

1. A tender is created and `PCA` + `PPT` are uploaded.
2. `ProcessDocument` analyzes each document asynchronously.
3. Criteria, specifications, and insights are persisted.
4. `GenerateTechnicalMemory` groups judgment criteria into dynamic sections.
5. `GenerateTechnicalMemorySection` generates each section and updates status/metrics.
6. Users review the memory, regenerate sections if needed, and export to PDF/Markdown.

## For developers

### Tech stack

- PHP 8.4
- Laravel 12
- Livewire 4
- Pest 4
- Laravel AI SDK (`laravel/ai`)
- Tailwind CSS 4
- SQLite by default

### Local setup

#### Quick option

1. Copy environment variables and create local SQLite file:

```bash
cp .env.example .env
php -r "file_exists('database/database.sqlite') || touch('database/database.sqlite');"
```

2. Install and bootstrap the project:

```bash
composer setup
```

3. Configure at least one AI provider key in `.env` (OpenAI is the default):

```dotenv
OPENAI_API_KEY=your_key
```

4. Run the full development environment (server, 3 queue workers, and Vite):

```bash
composer dev
```

Then open the app and start from the tenders list.

#### Manual option

```bash
composer install
npm install
php artisan key:generate
php artisan migrate
php artisan serve
php artisan queue:work --timeout=300 --tries=1
npm run dev
```

### Domain map

- `app/Livewire/Tenders/CreateTender.php`: tender creation and document upload.
- `app/Actions/Documents/ProcessDocumentAction.php`: extraction and persistence for PCA/PPT data.
- `app/Ai/Agents/DocumentAnalyzer.php`: structured document analysis.
- `app/Ai/Agents/PcaJudgmentCriteriaExtractorAgent.php`: dedicated extraction of judgment criteria.
- `app/Actions/Tenders/GenerateTechnicalMemoryAction.php`: dynamic section orchestration.
- `app/Jobs/GenerateTechnicalMemorySection.php`: per-section generation, quality gate, and metrics.
- `app/Ai/Agents/TechnicalMemoryDynamicSectionAgent.php`: section drafting.
- `app/Ai/Agents/TechnicalMemorySectionEditorAgent.php`: post-generation style editing.
- `app/Livewire/TechnicalMemories/ShowMemory.php`: memory page and section regeneration.
- `app/Livewire/TechnicalMemories/OperationalMetrics.php`: internal metrics dashboard.

### Useful routes

- `/`: redirects to tenders list.
- `/tenders/create`: create a new tender.
- `/technical-memories/operational-metrics`: internal monitoring dashboard.

### Testing and quality

```bash
php artisan test --compact
vendor/bin/pint --dirty --format agent
```

To run one specific test:

```bash
php artisan test --compact tests/Feature/Jobs/GenerateTechnicalMemorySectionTest.php
```

### Operations and maintenance

- `deadline_date` is stored as text to support natural language deadlines.
- The system depends on active queue workers for analysis and generation.
- Old metrics are purged daily with `technical-memory:purge-metrics`.
- If code changes are not reflected in running jobs:

```bash
php artisan queue:restart
php artisan optimize:clear
```

## Troubleshooting

- Stuck jobs: verify active workers (`composer dev` or `php artisan queue:work ...`).
- Memory generation not progressing: check section states in the memory view and application logs.
- Frontend changes not visible: run `npm run dev` or rebuild with `npm run build`.
- Vite asset runtime errors: restart `npm run dev`.
