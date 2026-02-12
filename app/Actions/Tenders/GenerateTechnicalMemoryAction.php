<?php

declare(strict_types=1);

namespace App\Actions\Tenders;

use App\Jobs\GenerateTechnicalMemorySection;
use App\Models\Tender;
use App\Support\TechnicalMemorySections;

final class GenerateTechnicalMemoryAction
{
    public function __invoke(Tender $tender): void
    {
        $criteria = $tender->extractedCriteria()
            ->orderBy('id')
            ->get();

        $pcaData = [
            'criteria' => $criteria
                ->map(fn ($item) => [
                    'section_number' => $item->section_number,
                    'section_title' => $item->section_title,
                    'description' => $item->description,
                    'priority' => $item->priority,
                    'metadata' => $item->metadata,
                ])
                ->all(),
            'evaluation_points' => $criteria
                ->map(fn ($item) => [
                    'section_number' => $item->section_number,
                    'section_title' => $item->section_title,
                    'priority' => $item->priority,
                    'points' => $this->extractEvaluationPoints((string) $item->description, is_array($item->metadata) ? $item->metadata : []),
                ])
                ->all(),
            'insights' => $tender->documentInsights()
                ->where('document_id', optional($tender->pcaDocument)->id)
                ->orderByDesc('importance')
                ->orderBy('id')
                ->get()
                ->map(fn ($item) => [
                    'section_reference' => $item->section_reference,
                    'topic' => $item->topic,
                    'requirement_type' => $item->requirement_type,
                    'importance' => $item->importance,
                    'statement' => $item->statement,
                    'evidence_excerpt' => $item->evidence_excerpt,
                ])
                ->all(),
        ];

        $pptData = [
            'specifications' => $tender->extractedSpecifications()
                ->orderBy('id')
                ->get()
                ->map(fn ($item) => [
                    'section_number' => $item->section_number,
                    'section_title' => $item->section_title,
                    'technical_description' => $item->technical_description,
                    'requirements' => $item->requirements,
                    'deliverables' => $item->deliverables,
                    'metadata' => $item->metadata,
                ])
                ->all(),
            'insights' => $tender->documentInsights()
                ->where('document_id', optional($tender->pptDocument)->id)
                ->orderByDesc('importance')
                ->orderBy('id')
                ->get()
                ->map(fn ($item) => [
                    'section_reference' => $item->section_reference,
                    'topic' => $item->topic,
                    'requirement_type' => $item->requirement_type,
                    'importance' => $item->importance,
                    'statement' => $item->statement,
                    'evidence_excerpt' => $item->evidence_excerpt,
                ])
                ->all(),
        ];

        $memory = $tender->technicalMemory()->updateOrCreate(
            ['tender_id' => $tender->id],
            [
                'title' => 'Memoria Técnica - '.$tender->title,
                'introduction' => null,
                'company_presentation' => null,
                'technical_approach' => null,
                'methodology' => null,
                'team_structure' => null,
                'timeline' => null,
                'timeline_plan' => null,
                'quality_assurance' => null,
                'risk_management' => null,
                'compliance_matrix' => null,
                'status' => 'draft',
                'generated_file_path' => null,
                'generated_at' => null,
            ]
        );

        foreach (TechnicalMemorySections::fields() as $sectionField) {
            GenerateTechnicalMemorySection::dispatch(
                technicalMemoryId: $memory->id,
                section: $sectionField,
                pcaData: $pcaData,
                pptData: $pptData,
            );
        }
    }

    /**
     * @param  array<string,mixed>  $metadata
     * @return array<int,string>
     */
    private function extractEvaluationPoints(string $description, array $metadata): array
    {
        $metadataPoints = collect([
            $metadata['evaluation_points'] ?? null,
            $metadata['criteria_points'] ?? null,
            $metadata['scoring_points'] ?? null,
            $metadata['puntos_evaluacion'] ?? null,
            $metadata['points'] ?? null,
        ])
            ->flatten(1)
            ->map(fn (mixed $point): string => trim((string) $point))
            ->filter(fn (string $point): bool => $point !== '')
            ->values();

        if ($metadataPoints->isNotEmpty()) {
            return $metadataPoints->take(8)->all();
        }

        $text = str_replace(["\r\n", "\r"], "\n", $description);

        $points = collect(preg_split('/\n+|\s*;\s*/', $text) ?: [])
            ->map(function (string $segment): string {
                $segment = trim($segment);
                $segment = preg_replace('/^[-*]\s+/', '', $segment) ?? $segment;
                $segment = preg_replace('/^\d+[\)\.-]?\s+/', '', $segment) ?? $segment;

                return trim($segment);
            })
            ->filter(fn (string $segment): bool => $segment !== '')
            ->unique()
            ->values();

        if ($points->isEmpty()) {
            return [$description];
        }

        return $points->take(8)->all();
    }
}
