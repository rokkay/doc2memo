<?php

namespace App\Http\Controllers;

use App\Models\TechnicalMemory;
use App\Models\Tender;
use App\Support\TechnicalMemorySections;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class TechnicalMemoryController extends Controller
{
    public function show(Tender $tender): View
    {
        return view('technical-memories.show', compact('tender'));
    }

    public function download(TechnicalMemory $technicalMemory): StreamedResponse
    {
        if (! $technicalMemory->generated_file_path || ! Storage::exists($technicalMemory->generated_file_path)) {
            abort(404, 'Technical memory file not found.');
        }

        return Storage::download(
            $technicalMemory->generated_file_path,
            'Memoria_Tecnica_'.$technicalMemory->tender->reference_number.'.pdf'
        );
    }

    public function downloadMarkdown(TechnicalMemory $technicalMemory): StreamedResponse
    {
        $filename = 'Memoria_Tecnica_'.($technicalMemory->tender->reference_number ?: $technicalMemory->id).'.md';
        $markdown = TechnicalMemorySections::buildMarkdownDocument($technicalMemory);

        return response()->streamDownload(
            callback: static function () use ($markdown): void {
                echo $markdown;
            },
            name: $filename,
            headers: ['Content-Type' => 'text/markdown; charset=UTF-8'],
        );
    }
}
