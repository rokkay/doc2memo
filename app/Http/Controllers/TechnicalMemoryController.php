<?php

namespace App\Http\Controllers;

use App\Models\TechnicalMemory;
use App\Models\Tender;
use App\Support\SectionTitleNormalizer;
use App\Support\TechnicalMemoryMarkdownBuilder;
use Illuminate\Support\Str;
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
        $technicalMemory->loadMissing('sections');

        $rawSections = $technicalMemory->sections
            ->map(function ($section): ?array {
                $content = trim((string) ($section->content ?? ''));

                if ($content === '') {
                    return null;
                }

                return [
                    'title' => SectionTitleNormalizer::heading($section->section_number, (string) $section->section_title),
                    'content' => $content,
                ];
            })
            ->filter()
            ->values()
            ->all();

        $sections = [];
        $toc = [];

        foreach ($rawSections as $sectionIndex => $section) {
            $sectionId = 'toc-section-'.($sectionIndex + 1);
            $subsections = [];
            $subsectionIndex = 0;

            $contentWithAnchors = preg_replace_callback(
                '/^(#{2,4})\s+(.+)$/m',
                function (array $matches) use (&$subsections, &$subsectionIndex, $sectionId): string {
                    $level = strlen($matches[1]);
                    $headingText = trim(preg_replace('/\s+\{#.+\}$/', '', $matches[2]) ?? $matches[2]);

                    $subsectionIndex++;
                    $subsectionId = $sectionId.'-sub-'.$subsectionIndex;

                    $subsections[] = [
                        'id' => $subsectionId,
                        'title' => $headingText,
                        'level' => max(2, $level),
                    ];

                    return '<h'.$level.' id="'.$subsectionId.'">'.$headingText.'</h'.$level.'>';
                },
                (string) $section['content'],
            ) ?? (string) $section['content'];

            $sections[] = [
                'id' => $sectionId,
                'title' => (string) $section['title'],
                'html' => Str::markdown($contentWithAnchors),
                'subsections' => $subsections,
            ];

            $toc[] = [
                'id' => $sectionId,
                'title' => (string) $section['title'],
                'level' => 1,
            ];

            foreach ($subsections as $subsection) {
                $toc[] = [
                    'id' => (string) $subsection['id'],
                    'title' => (string) $subsection['title'],
                    'level' => 2,
                ];
            }
        }

        return pdf()
            ->view('technical-memories.pdf', [
                'technicalMemory' => $technicalMemory,
                'sections' => $sections,
                'toc' => $toc,
            ])
            ->headerView('technical-memories.pdf-header', [
                'technicalMemory' => $technicalMemory,
            ])
            ->footerView('technical-memories.pdf-footer')
            ->format(Format::A4)
            ->margins(top: 34, right: 18, bottom: 30, left: 18, unit: 'mm')
            ->name('Memoria_Tecnica_'.($technicalMemory->tender->reference_number ?: $technicalMemory->id).'.pdf')
            ->download();
    }

    public function downloadMarkdown(TechnicalMemory $technicalMemory): StreamedResponse
    {
        $technicalMemory->loadMissing('sections', 'tender');

        $filename = 'Memoria_Tecnica_'.($technicalMemory->tender->reference_number ?: $technicalMemory->id).'.md';
        $markdown = TechnicalMemoryMarkdownBuilder::build($technicalMemory);

        return response()->streamDownload(
            callback: static function () use ($markdown): void {
                echo $markdown;
            },
            name: $filename,
            headers: ['Content-Type' => 'text/markdown; charset=UTF-8'],
        );
    }
}
