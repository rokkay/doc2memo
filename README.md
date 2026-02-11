# Doc2Memo

Doc2Memo helps teams prepare a competitive technical report ("Memoria tecnica") for public tenders.

The app workflow is:

1. Create a tender and upload two source documents:
   - `PCA` (administrative conditions)
   - `PPT` (technical specifications)
2. Documents are processed by queue jobs and analyzed with Laravel AI agents.
3. Extracted criteria, specifications, and insights are stored in the database.
4. The user triggers memory generation, which runs async and produces the final technical memory.

Sample tender documents are available at `docs/samples/*`.

## Tech Stack

- PHP 8.4
- Laravel 12
- Livewire 4
- Pest 4
- Laravel AI SDK (`laravel/ai`)
- Tailwind CSS 4

## Local Setup

1. Install dependencies:

```bash
composer install
npm install
```

2. Configure environment:

```bash
cp .env.example .env
php artisan key:generate
```

3. Run database migrations:

```bash
php artisan migrate
```

4. Start app services:

```bash
php artisan serve
php artisan queue:work
npm run dev
```

Queue workers are required for both document analysis and memory generation.

## Important Runtime Notes

- The `deadline_date` field is stored as plain text (not a date type), because tenders often describe deadlines in natural language.
- Document and memory actions are asynchronous; UI feedback appears immediately while jobs continue in background.
- If code changes are not reflected in processing behavior, restart workers:

```bash
php artisan queue:restart
php artisan optimize:clear
```

## Main Domain Flow

- `app/Livewire/Tenders/CreateTender.php`: tender creation + document upload
- `app/Jobs/ProcessDocument.php`: per-document analysis job
- `app/Actions/Documents/ProcessDocumentAction.php`: extraction + persistence
- `app/Jobs/GenerateTechnicalMemory.php`: async memory generation job
- `app/Actions/Tenders/GenerateTechnicalMemoryAction.php`: memory building + save
- `app/Ai/Agents/DocumentAnalyzer.php`: PCA/PPT structured extraction
- `app/Ai/Agents/TechnicalMemoryGenerator.php`: final technical memory generation

## Testing

Run all tests:

```bash
php artisan test --compact
```

Format changed files:

```bash
php vendor/bin/pint --dirty --format agent
```

## Troubleshooting

- **Jobs not moving / UI stuck in processing**: verify `queue:work` is running.
- **Old behavior after code changes**: run `php artisan queue:restart`.
- **AI calls timing out in browser action**: make sure action is queued (current design is async).
- **Vite assets not updating**: run `npm run dev`.
