<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('renders the operational metrics page route', function (): void {
    $this->get(route('technical-memories.operational-metrics'))
        ->assertSuccessful()
        ->assertSee('Metricas operativas')
        ->assertSee('First pass');
});
