<?php

declare(strict_types=1);

namespace App\ViewData;

use App\Enums\TechnicalMemorySectionStatus;
use App\Models\Tender;
use App\Support\SectionTitleNormalizer;
use App\Support\TechnicalMemoryMarkdownBuilder;

final class TechnicalMemoryViewData
{
    /**
     * @param  array<int,array{id:int,anchor:string,title:string,content:string,points:float,weight:float,criteria_count:int,status:string}>  $sections
     * @param  array<int,array{id:int,anchor:string,title:string,content:string,points:float,weight:float,criteria_count:int,status:string}>  $inProgressSections
     * @param  array<int,array{id:int,anchor:string,title:string,content:string,points:float,weight:float,criteria_count:int,status:string}>  $failedSections
     */
    public function __construct(
        public readonly bool $hasMemory,
        public readonly bool $isGenerating,
        public readonly array $sections,
        public readonly array $inProgressSections,
        public readonly array $failedSections,
        public readonly int $completedCount,
        public readonly int $totalCount,
        public readonly float $totalPoints,
        public readonly int $progressPercent,
        public readonly string $markdownExport,
    ) {}

    public static function fromTender(Tender $tender): self
    {
        $memory = $tender->technicalMemory;

        if (! $memory) {
            return new self(
                hasMemory: false,
                isGenerating: false,
                sections: [],
                inProgressSections: [],
                failedSections: [],
                completedCount: 0,
                totalCount: 0,
                totalPoints: 0.0,
                progressPercent: 0,
                markdownExport: '',
            );
        }

        $sections = $memory->sections
            ->sortBy('sort_order')
            ->map(function ($section): array {
                $anchor = 'section-'.$section->id;

                return [
                    'id' => $section->id,
                    'anchor' => $anchor,
                    'title' => SectionTitleNormalizer::heading($section->section_number, (string) $section->section_title),
                    'content' => trim((string) ($section->content ?? '')),
                    'points' => (float) ($section->total_points ?? 0),
                    'weight' => (float) ($section->weight_percent ?? 0),
                    'criteria_count' => (int) ($section->criteria_count ?? 0),
                    'status' => $section->status instanceof TechnicalMemorySectionStatus
                        ? $section->status->value
                        : (string) $section->status,
                ];
            })
            ->values()
            ->all();

        $completedCount = count(array_filter(
            $sections,
            static fn (array $section): bool => $section['status'] === TechnicalMemorySectionStatus::Completed->value,
        ));

        $inProgressSections = array_values(array_filter(
            $sections,
            static fn (array $section): bool => in_array($section['status'], TechnicalMemorySectionStatus::activeValues(), true),
        ));

        $failedSections = array_values(array_filter(
            $sections,
            static fn (array $section): bool => $section['status'] === TechnicalMemorySectionStatus::Failed->value,
        ));

        $totalCount = count($sections);
        $totalPoints = array_reduce(
            $sections,
            static fn (float $carry, array $section): float => $carry + (float) $section['points'],
            0.0,
        );

        $progressPercent = $totalCount > 0
            ? (int) round(($completedCount / $totalCount) * 100)
            : 0;

        return new self(
            hasMemory: true,
            isGenerating: $memory->status === 'draft',
            sections: $sections,
            inProgressSections: $inProgressSections,
            failedSections: $failedSections,
            completedCount: $completedCount,
            totalCount: $totalCount,
            totalPoints: $totalPoints,
            progressPercent: $progressPercent,
            markdownExport: TechnicalMemoryMarkdownBuilder::build($memory),
        );
    }
}
