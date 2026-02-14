<?php

use App\Jobs\ProcessDocument;
use App\Livewire\Tenders\CreateTender;
use App\Models\Document;
use App\Models\Tender;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

uses()->group('livewire');
uses(RefreshDatabase::class);

it('renders successfully', function (): void {
    Livewire::test(CreateTender::class)
        ->assertOk()
        ->assertSee('Nueva Licitación')
        ->assertSee('Título de la Licitación')
        ->assertSee('PCA (Pliego de Condiciones Administrativas)')
        ->assertSee('PPT (Pliego de Prescripciones Técnicas)');
});

it('validates required title', function (): void {
    Livewire::test(CreateTender::class)
        ->set('form.title', '')
        ->set('pcaFile', UploadedFile::fake()->create('pca.pdf', 100, 'application/pdf'))
        ->set('pptFile', UploadedFile::fake()->create('ppt.pdf', 100, 'application/pdf'))
        ->call('save')
        ->assertHasErrors(['form.title' => 'required']);
});

it('validates pca file is required', function (): void {
    Livewire::test(CreateTender::class)
        ->set('form.title', 'Test Tender')
        ->set('pptFile', UploadedFile::fake()->create('ppt.pdf', 100, 'application/pdf'))
        ->call('save')
        ->assertHasErrors(['pcaFile' => 'required']);
});

it('validates ppt file is required', function (): void {
    Livewire::test(CreateTender::class)
        ->set('form.title', 'Test Tender')
        ->set('pcaFile', UploadedFile::fake()->create('pca.pdf', 100, 'application/pdf'))
        ->call('save')
        ->assertHasErrors(['pptFile' => 'required']);
});

it('validates file types', function (): void {
    Livewire::test(CreateTender::class)
        ->set('form.title', 'Test Tender')
        ->set('pcaFile', UploadedFile::fake()->create('pca.exe', 100))
        ->set('pptFile', UploadedFile::fake()->create('ppt.exe', 100))
        ->call('save')
        ->assertHasErrors(['pcaFile' => 'mimes'])
        ->assertHasErrors(['pptFile' => 'mimes']);
});

it('creates tender with documents', function (): void {
    Storage::fake('local');
    Queue::fake();

    $component = Livewire::test(CreateTender::class)
        ->set('form.title', 'Test Tender Title')
        ->set('form.issuing_company', 'Test Company')
        ->set('form.reference_number', 'REF-123')
        ->set('form.description', 'Test description')
        ->set('pcaFile', UploadedFile::fake()->create('pca.pdf', 100, 'application/pdf'))
        ->set('pptFile', UploadedFile::fake()->create('ppt.pdf', 100, 'application/pdf'))
        ->call('save')
        ->assertHasNoErrors();

    $tender = Tender::where('title', 'Test Tender Title')->first();
    expect($tender)->not->toBeNull();
    $component->assertRedirect(route('tenders.show', $tender));

    expect($tender->issuing_company)->toBe('Test Company');
    expect($tender->reference_number)->toBe('REF-123');
    expect($tender->status)->toBe('analyzing');

    $documents = Document::where('tender_id', $tender->id)->get();
    expect($documents)->toHaveCount(2);
    expect($documents->pluck('document_type'))->toContain('pca', 'ppt');

    Queue::assertPushed(ProcessDocument::class, 2);
});

it('accepts deadline date as free text', function (): void {
    Storage::fake('local');
    Queue::fake();

    Livewire::test(CreateTender::class)
        ->set('form.title', 'Tender with textual deadline')
        ->set('form.deadline_date', '15 dias naturales desde la publicacion')
        ->set('pcaFile', UploadedFile::fake()->create('pca.pdf', 100, 'application/pdf'))
        ->set('pptFile', UploadedFile::fake()->create('ppt.pdf', 100, 'application/pdf'))
        ->call('save')
        ->assertHasNoErrors();

    $tender = Tender::query()->where('title', 'Tender with textual deadline')->firstOrFail();

    expect($tender->deadline_date)->toBe('15 dias naturales desde la publicacion');
});

it('shows validation errors when submitting', function (): void {
    Livewire::test(CreateTender::class)
        ->set('form.title', '')
        ->set('pcaFile', null)
        ->set('pptFile', null)
        ->call('save')
        ->assertHasErrors(['form.title', 'pcaFile', 'pptFile']);
});

it('can remove pca file after selection', function (): void {
    $component = Livewire::test(CreateTender::class)
        ->set('pcaFile', UploadedFile::fake()->create('pca.pdf', 100, 'application/pdf'));

    expect($component->get('pcaFile'))->not->toBeNull();

    $component->call('removePcaFile');

    expect($component->get('pcaFile'))->toBeNull();
});

it('can remove ppt file after selection', function (): void {
    $component = Livewire::test(CreateTender::class)
        ->set('pptFile', UploadedFile::fake()->create('ppt.pdf', 100, 'application/pdf'));

    expect($component->get('pptFile'))->not->toBeNull();

    $component->call('removePptFile');

    expect($component->get('pptFile'))->toBeNull();
});
