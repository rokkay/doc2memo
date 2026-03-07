<?php

declare(strict_types=1);

use App\Actions\Documents\PersistAiCostEntriesAction;
use App\Enums\AiCostCategory;
use App\Models\AiCostEntry;
use App\Models\Document;
use App\Models\Tender;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

use function Pest\Laravel\assertDatabaseHas;

uses(TestCase::class, RefreshDatabase::class);

it('persists analyzer and dedicated extractor ai cost entries', function (): void {
    $tender = Tender::factory()->create();
    $document = Document::factory()->create(['tender_id' => $tender->id]);

    $breakdown = [
        'document_analyzer' => [
            'model_name' => 'openai/gpt-5-mini',
            'input_chars' => 1200,
            'output_chars' => 300,
            'estimated_input_units' => 1.2,
            'estimated_output_units' => 0.3,
            'estimated_cost_usd' => 0.1234,
            'status' => 'completed',
            'token_usage' => [
                'available' => true,
                'prompt_tokens' => 77,
                'completion_tokens' => 11,
                'cache_write_input_tokens' => 3,
                'cache_read_input_tokens' => 2,
                'reasoning_tokens' => 5,
            ],
        ],
        'dedicated_judgment_extractor' => [
            'model_name' => 'openai/gpt-5-mini',
            'input_chars' => 0,
            'output_chars' => 0,
            'estimated_input_units' => 0,
            'estimated_output_units' => 0,
            'estimated_cost_usd' => 0,
            'status' => 'skipped',
        ],
    ];

    (new PersistAiCostEntriesAction)($document, $breakdown);

    assertDatabaseHas('ai_cost_entries', [
        'document_id' => $document->id,
        'agent_key' => 'document_analyzer',
        'category' => AiCostCategory::DocumentAnalyzer->value,
        'prompt_tokens' => 77,
        'completion_tokens' => 11,
        'status' => 'completed',
    ]);

    assertDatabaseHas('ai_cost_entries', [
        'document_id' => $document->id,
        'agent_key' => 'dedicated_judgment_extractor',
        'category' => AiCostCategory::DedicatedJudgmentExtractor->value,
        'status' => 'skipped',
    ]);
});

it('skips malformed breakdown payload entries safely', function (): void {
    $tender = Tender::factory()->create();
    $document = Document::factory()->create(['tender_id' => $tender->id]);

    (new PersistAiCostEntriesAction)($document, [
        'document_analyzer' => 'invalid',
    ]);

    expect(AiCostEntry::query()->where('document_id', $document->id)->count())->toBe(0);
});
