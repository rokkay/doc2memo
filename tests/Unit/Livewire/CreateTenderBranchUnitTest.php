<?php

declare(strict_types=1);

use App\Livewire\Tenders\CreateTender;
use League\Flysystem\UnableToRetrieveMetadata;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Tests\TestCase;

uses(TestCase::class);

it('resets pca file and returns when upload has expired before validation', function (): void {
    $component = new CreateTender;

    $pca = \Mockery::mock(TemporaryUploadedFile::class);
    $pca->shouldReceive('exists')->once()->andReturn(false);

    $component->pcaFile = $pca;
    $component->pptFile = null;

    $component->save();

    expect($component->pcaFile)->toBeNull()
        ->and($component->isSubmitting)->toBeFalse();
});

it('resets ppt file and returns when upload has expired before validation', function (): void {
    $component = new CreateTender;

    $ppt = \Mockery::mock(TemporaryUploadedFile::class);
    $ppt->shouldReceive('exists')->once()->andReturn(false);

    $component->pcaFile = null;
    $component->pptFile = $ppt;

    $component->save();

    expect($component->pptFile)->toBeNull()
        ->and($component->isSubmitting)->toBeFalse();
});

it('handles metadata exception during validation and resets expired files', function (): void {
    $component = new class extends CreateTender
    {
        public function validate($rules = null, $messages = [], $attributes = []): array
        {
            throw UnableToRetrieveMetadata::visibility('missing');
        }
    };

    $pca = \Mockery::mock(TemporaryUploadedFile::class);
    $pca->shouldReceive('exists')->times(2)->andReturn(true, false);

    $ppt = \Mockery::mock(TemporaryUploadedFile::class);
    $ppt->shouldReceive('exists')->times(2)->andReturn(true, false);

    $component->pcaFile = $pca;
    $component->pptFile = $ppt;

    $component->save();

    expect($component->pcaFile)->toBeNull()
        ->and($component->pptFile)->toBeNull()
        ->and($component->isSubmitting)->toBeFalse();
});
