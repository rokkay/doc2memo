<?php

declare(strict_types=1);

namespace App\Data;

use App\Models\ExtractedCriterion;

final class JudgmentCriterionData
{
    /**
     * @param  array<string,mixed>|null  $metadata
     */
    public function __construct(
        public readonly ?string $sectionNumber,
        public readonly string $sectionTitle,
        public readonly string $description,
        public readonly string $priority,
        public readonly string $criterionType,
        public readonly ?float $scorePoints,
        public readonly string $groupKey,
        public readonly string $source,
        public readonly ?float $confidence,
        public readonly ?array $metadata,
    ) {}

    public static function fromModel(ExtractedCriterion $criterion): self
    {
        return new self(
            sectionNumber: $criterion->section_number,
            sectionTitle: (string) $criterion->section_title,
            description: (string) $criterion->description,
            priority: (string) $criterion->priority,
            criterionType: (string) $criterion->criterion_type,
            scorePoints: $criterion->score_points !== null ? (float) $criterion->score_points : null,
            groupKey: (string) ($criterion->group_key ?? ''),
            source: (string) ($criterion->source ?? 'analyzer'),
            confidence: $criterion->confidence !== null ? (float) $criterion->confidence : null,
            metadata: is_array($criterion->metadata) ? $criterion->metadata : null,
        );
    }

    /**
     * @param  array<string,mixed>  $payload
     */
    public static function fromArray(array $payload): self
    {
        return new self(
            sectionNumber: is_string($payload['section_number'] ?? null) ? $payload['section_number'] : null,
            sectionTitle: (string) ($payload['section_title'] ?? 'Sin sección'),
            description: (string) ($payload['description'] ?? ''),
            priority: (string) ($payload['priority'] ?? 'mandatory'),
            criterionType: (string) ($payload['criterion_type'] ?? 'judgment'),
            scorePoints: self::toNullableFloat($payload['score_points'] ?? null),
            groupKey: (string) ($payload['group_key'] ?? ''),
            source: (string) ($payload['source'] ?? 'analyzer'),
            confidence: self::toNullableFloat($payload['confidence'] ?? null),
            metadata: is_array($payload['metadata'] ?? null) ? $payload['metadata'] : null,
        );
    }

    /**
     * @return array{section_number:?string,section_title:string,description:string,priority:string,criterion_type:string,score_points:?float,group_key:string,source:string,confidence:?float,metadata:?array<string,mixed>}
     */
    public function toArray(): array
    {
        return [
            'section_number' => $this->sectionNumber,
            'section_title' => $this->sectionTitle,
            'description' => $this->description,
            'priority' => $this->priority,
            'criterion_type' => $this->criterionType,
            'score_points' => $this->scorePoints,
            'group_key' => $this->groupKey,
            'source' => $this->source,
            'confidence' => $this->confidence,
            'metadata' => $this->metadata,
        ];
    }

    private static function toNullableFloat(mixed $value): ?float
    {
        if ($value === null) {
            return null;
        }

        if (is_int($value) || is_float($value)) {
            return (float) $value;
        }

        if (! is_string($value)) {
            return null;
        }

        $normalized = str_replace(',', '.', trim($value));

        return is_numeric($normalized) ? (float) $normalized : null;
    }
}
