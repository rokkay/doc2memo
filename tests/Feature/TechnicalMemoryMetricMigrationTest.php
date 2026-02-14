<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;

uses(RefreshDatabase::class);

it('creates technical memory metric tables', function (): void {
    expect(Schema::hasTable('technical_memory_metric_runs'))->toBeTrue()
        ->and(Schema::hasTable('technical_memory_metric_events'))->toBeTrue();
});
