<?php

namespace App\Support;

use App\Models\TechnicalMemory;

class TechnicalMemoryMarkdownBuilder
{
    public static function build(TechnicalMemory $memory): string
    {
        $title = trim((string) ($memory->title ?: 'Memoria Técnica'));

        $sections = $memory->sections()
            ->whereNotNull('content')
            ->where('content', '!=', '')
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get()
            ->map(function ($section): string {
                $heading = SectionTitleNormalizer::heading($section->section_number, (string) $section->section_title);

                return '## '.$heading."\n\n".trim((string) $section->content);
            })
            ->all();

        $markdown = collect(array_merge(["# {$title}"], $sections))
            ->implode("\n\n");

        return trim($markdown)."\n";
    }
}
