<?php

use App\Livewire\Tenders\TenderList;
use App\Models\Tender;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses()->group('livewire');
uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->seed(\Database\Seeders\DatabaseSeeder::class);
});

it('renders successfully', function (): void {
    Livewire::test(TenderList::class)
        ->assertOk();
});

it('displays tenders list', function (): void {
    Tender::factory()->create([
        'title' => 'Test Tender Title',
        'issuing_company' => 'Test Company',
    ]);

    Livewire::test(TenderList::class)
        ->assertSee('Test Tender Title')
        ->assertSee('Test Company');
});

it('can search tenders by title', function (): void {
    Tender::factory()->create(['title' => 'Special Tender Name']);
    Tender::factory()->create(['title' => 'Other Tender']);

    Livewire::test(TenderList::class)
        ->set('search', 'Special')
        ->assertSee('Special Tender Name')
        ->assertDontSee('Other Tender');
});

it('can filter tenders by status', function (): void {
    Tender::factory()->create([
        'title' => 'Pending Tender',
        'status' => 'pending',
    ]);
    Tender::factory()->create([
        'title' => 'Completed Tender',
        'status' => 'completed',
    ]);

    Livewire::test(TenderList::class)
        ->set('statusFilter', 'pending')
        ->assertSee('Pending Tender')
        ->assertDontSee('Completed Tender');
});

it('displays empty state when no tenders', function (): void {
    Tender::query()->delete();

    Livewire::test(TenderList::class)
        ->assertSee('No hay licitaciones registradas')
        ->assertSee('Crear primera licitación');
});

it('has link to create new tender', function (): void {
    Livewire::test(TenderList::class)
        ->assertSee('Nueva Licitación')
        ->assertSee(route('tenders.create'));
});

it('uses fixed table layout with truncated long title cells', function (): void {
    Tender::factory()->create([
        'title' => str_repeat('Titulo de licitacion muy largo ', 8),
        'issuing_company' => 'Empresa larga de prueba',
    ]);

    Livewire::test(TenderList::class)
        ->assertSeeHtml('table-fixed')
        ->assertSeeHtml('line-clamp-2');
});
