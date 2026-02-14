<?php

declare(strict_types=1);

namespace App\Data;

final class TechnicalMemorySectionData
{
    /**
     * @param  array<int,JudgmentCriterionData>  $criteria
     */
    public function __construct(
        public readonly string $groupKey,
        public readonly ?string $sectionNumber,
        public readonly string $sectionTitle,
        public readonly float $totalPoints,
        public readonly int $criteriaCount,
        public readonly array $criteria,
        public readonly string $sortKey,
    ) {}

    /**
     * @param  array<string,mixed>  $payload
     */
    public static function fromArray(array $payload): self
    {
        $criteria = collect($payload['criteria'] ?? [])
            ->filter(fn (mixed $criterion): bool => is_array($criterion))
            ->map(fn (array $criterion): JudgmentCriterionData => JudgmentCriterionData::fromArray($criterion))
            ->values()
            ->all();

        return new self(
            groupKey: (string) ($payload['group_key'] ?? ''),
            sectionNumber: is_string($payload['section_number'] ?? null) ? $payload['section_number'] : null,
            sectionTitle: (string) ($payload['section_title'] ?? 'Sin sección'),
            totalPoints: (float) ($payload['total_points'] ?? 0.0),
            criteriaCount: (int) ($payload['criteria_count'] ?? count($criteria)),
            criteria: $criteria,
            sortKey: (string) ($payload['sort_key'] ?? ''),
        );
    }

    /**
     * @return array{group_key:string,section_number:?string,section_title:string,total_points:float,criteria_count:int,criteria:array<int,array{section_number:?string,section_title:string,description:string,priority:string,criterion_type:string,score_points:?float,group_key:string,metadata:?array<string,mixed>}>,sort_key:string}
     */
    public function toArray(): array
    {
        return [
            'group_key' => $this->groupKey,
            'section_number' => $this->sectionNumber,
            'section_title' => $this->sectionTitle,
            'total_points' => $this->totalPoints,
            'criteria_count' => $this->criteriaCount,
            'criteria' => array_map(
                static fn (JudgmentCriterionData $criterion): array => $criterion->toArray(),
                $this->criteria,
            ),
            'sort_key' => $this->sortKey,
        ];
    }
}
