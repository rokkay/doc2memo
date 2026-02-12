<?php

namespace App\Http\Controllers;

use App\Models\TechnicalMemory;
use App\Models\Tender;
use App\Support\TechnicalMemorySections;
use Illuminate\View\View;
use Spatie\LaravelPdf\Enums\Format;
use Spatie\LaravelPdf\PdfBuilder;
use Symfony\Component\HttpFoundation\StreamedResponse;

use function Spatie\LaravelPdf\Support\pdf;

class TechnicalMemoryController extends Controller
{
    public function show(Tender $tender): View
    {
        return view('technical-memories.show', compact('tender'));
    }

    public function download(TechnicalMemory $technicalMemory): PdfBuilder
    {
        $technicalMemory->loadMissing('tender');

        $sections = collect(TechnicalMemorySections::fields())
            ->map(function (string $field) use ($technicalMemory): ?array {
                $content = trim((string) ($technicalMemory->{$field} ?? ''));

                if ($content === '') {
                    return null;
                }

                return [
                    'title' => TechnicalMemorySections::title($field),
                    'content' => $content,
                ];
            })
            ->filter()
            ->values()
            ->all();

        return pdf()
            ->view('technical-memories.pdf', [
                'technicalMemory' => $technicalMemory,
                'sections' => $sections,
            ])
            ->headerView('technical-memories.pdf-header', [
                'technicalMemory' => $technicalMemory,
            ])
            ->footerView('technical-memories.pdf-footer')
            ->format(Format::A4)
            ->margins(top: 24, right: 14, bottom: 20, left: 14, unit: 'mm')
            ->name('Memoria_Tecnica_'.($technicalMemory->tender->reference_number ?: $technicalMemory->id).'.pdf')
            ->download();
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
