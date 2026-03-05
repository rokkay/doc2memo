<?php

declare(strict_types=1);

use App\Models\Document;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

it('renders the document show page', function (): void {
    $document = Document::factory()->create();

    $this->get(route('documents.show', $document))
        ->assertSuccessful()
        ->assertSee($document->original_filename);
});

it('downloads the stored document file', function (): void {
    Storage::fake('local');

    Storage::disk('local')->put('documents/test-file.pdf', 'dummy file content');

    $document = Document::factory()->create([
        'file_path' => 'documents/test-file.pdf',
        'original_filename' => 'licitacion.pdf',
    ]);

    $this->get(route('documents.download', $document))
        ->assertSuccessful()
        ->assertHeader('content-disposition', 'attachment; filename=licitacion.pdf');
});
