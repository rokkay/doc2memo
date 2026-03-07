<?php

declare(strict_types=1);

use App\Jobs\ProcessDocument;
use App\Models\Document;
use App\Models\ExtractedCriterion;
use App\Models\ExtractedSpecification;
use App\Models\TechnicalMemory;
use App\Models\Tender;
use App\Services\TechnicalMemoryGenerationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

it('renders index and create pages', function (): void {
    $this->get(route('tenders.index'))->assertSuccessful();
    $this->get(route('tenders.create'))->assertSuccessful();
});

it('creates a tender and dispatches document processing jobs', function (): void {
    Storage::fake('local');
    Bus::fake();

    $response = $this->post(route('tenders.store'), [
        'title' => 'Licitacion de soporte',
        'issuing_company' => 'Ayuntamiento',
        'description' => 'Descripcion breve',
        'deadline_date' => now()->addWeek()->toDateString(),
        'reference_number' => 'EXP-2026-001',
        'pca_file' => UploadedFile::fake()->create('pca.pdf', 100, 'application/pdf'),
        'ppt_file' => UploadedFile::fake()->create('ppt.pdf', 100, 'application/pdf'),
    ]);

    $tender = Tender::query()->firstOrFail();

    $response->assertRedirect(route('tenders.show', $tender));

    expect($tender->status)->toBe('analyzing');
    expect($tender->documents()->count())->toBe(2);

    Bus::assertDispatched(ProcessDocument::class, 2);
});

it('returns validation errors when required files are missing', function (): void {
    $this->from(route('tenders.create'))
        ->post(route('tenders.store'), [
            'title' => 'Sin ficheros',
        ])
        ->assertRedirect(route('tenders.create'))
        ->assertSessionHasErrors(['pca_file', 'ppt_file']);
});

it('returns error flash when store throws exception', function (): void {
    Tender::creating(function (): void {
        throw new RuntimeException('forced failure');
    });

    $this->from(route('tenders.create'))
        ->post(route('tenders.store'), [
            'title' => 'Licitacion con error',
            'pca_file' => UploadedFile::fake()->create('pca.pdf', 100, 'application/pdf'),
            'ppt_file' => UploadedFile::fake()->create('ppt.pdf', 100, 'application/pdf'),
        ])
        ->assertRedirect(route('tenders.create'))
        ->assertSessionHas('error');

    Tender::flushEventListeners();
});

it('shows tender details', function (): void {
    $tender = Tender::factory()->create();

    $this->get(route('tenders.show', $tender))
        ->assertSuccessful()
        ->assertSee($tender->title);
});

it('analyzes uploaded documents and updates statuses', function (): void {
    Bus::fake();

    $tender = Tender::factory()->create(['status' => 'pending']);

    Document::factory()->create([
        'tender_id' => $tender->id,
        'status' => 'uploaded',
        'original_filename' => 'pca.pdf',
    ]);

    Document::factory()->create([
        'tender_id' => $tender->id,
        'status' => 'uploaded',
        'original_filename' => 'ppt.pdf',
    ]);

    $this->post(route('tenders.analyze', $tender))
        ->assertRedirect()
        ->assertSessionHas('success');

    expect($tender->fresh()->status)->toBe('analyzing');
    expect($tender->documents()->where('status', 'processing')->count())->toBe(2);

    Bus::assertDispatched(ProcessDocument::class, 2);
});

it('returns info when there are no uploaded documents to analyze', function (): void {
    $tender = Tender::factory()->create();

    Document::factory()->create([
        'tender_id' => $tender->id,
        'status' => 'analyzed',
    ]);

    $this->post(route('tenders.analyze', $tender))
        ->assertRedirect()
        ->assertSessionHas('info');
});

it('blocks memory generation when extracted inputs are missing', function (): void {
    $tender = Tender::factory()->create();

    $this->post(route('tenders.generate-memory', $tender))
        ->assertRedirect()
        ->assertSessionHas('error');
});

it('returns info when a memory already exists', function (): void {
    $tender = Tender::factory()->create();

    ExtractedCriterion::factory()->create(['tender_id' => $tender->id]);
    ExtractedSpecification::factory()->create(['tender_id' => $tender->id]);
    TechnicalMemory::factory()->create(['tender_id' => $tender->id]);

    $this->post(route('tenders.generate-memory', $tender))
        ->assertRedirect()
        ->assertSessionHas('info');
});

it('starts memory generation when prerequisites exist', function (): void {
    $tender = Tender::factory()->create();

    ExtractedCriterion::factory()->create(['tender_id' => $tender->id]);
    ExtractedSpecification::factory()->create(['tender_id' => $tender->id]);

    $service = Mockery::mock(TechnicalMemoryGenerationService::class);
    $service->shouldReceive('generate')
        ->once()
        ->withArgs(fn (Tender $passedTender): bool => $passedTender->is($tender));

    app()->instance(TechnicalMemoryGenerationService::class, $service);

    $this->post(route('tenders.generate-memory', $tender))
        ->assertRedirect()
        ->assertSessionHas('success');
});
