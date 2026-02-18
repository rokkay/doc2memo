<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;

uses(RefreshDatabase::class);

it('creates technical memory metric tables', function (): void {
    expect(Schema::hasTable('technical_memory_metric_runs'))->toBeTrue()
        ->and(Schema::hasTable('technical_memory_metric_events'))->toBeTrue()
        ->and(Schema::hasTable('ai_cost_entries'))->toBeTrue();
});

it('creates unified ai cost entry columns', function (): void {
    expect(Schema::hasColumns('ai_cost_entries', [
        'id',
        'tender_id',
        'document_id',
        'technical_memory_id',
        'technical_memory_section_id',
        'technical_memory_generation_metric_id',
        'run_id',
        'attempt',
        'category',
        'agent_key',
        'model_name',
        'status',
        'input_chars',
        'output_chars',
        'prompt_tokens',
        'completion_tokens',
        'cache_write_input_tokens',
        'cache_read_input_tokens',
        'reasoning_tokens',
        'estimated_input_units',
        'estimated_output_units',
        'estimated_cost_usd',
        'metadata',
    ]))->toBeTrue();
});

it('removes legacy ai cost columns from documents and generation metrics', function (): void {
    expect(Schema::hasColumns('documents', [
        'estimated_analysis_input_units',
        'estimated_analysis_output_units',
        'estimated_analysis_cost_usd',
        'analysis_cost_breakdown',
    ]))->toBeFalse()
        ->and(Schema::hasColumns('technical_memory_generation_metrics', [
            'estimated_input_units',
            'estimated_output_units',
            'estimated_cost_usd',
            'agent_cost_breakdown',
        ]))->toBeFalse();
});
