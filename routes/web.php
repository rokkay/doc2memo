<?php

use App\Http\Controllers\DocumentController;
use App\Http\Controllers\TechnicalMemoryController;
use App\Http\Controllers\TenderController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect()->route('tenders.index');
});

Route::resource('tenders', TenderController::class);

Route::post('tenders/{tender}/analyze', [TenderController::class, 'analyze'])
    ->name('tenders.analyze');

Route::post('tenders/{tender}/generate-memory', [TenderController::class, 'generateMemory'])
    ->name('tenders.generate-memory');

Route::get('documents/{document}', [DocumentController::class, 'show'])
    ->name('documents.show');

Route::get('documents/{document}/download', [DocumentController::class, 'download'])
    ->name('documents.download');

Route::get('tenders/{tender}/technical-memory', [TechnicalMemoryController::class, 'show'])
    ->name('technical-memories.show');

Route::get('technical-memories/{technicalMemory}/download', [TechnicalMemoryController::class, 'download'])
    ->name('technical-memories.download');
