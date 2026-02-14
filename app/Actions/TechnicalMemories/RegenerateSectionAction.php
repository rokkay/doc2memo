<?php

declare(strict_types=1);

namespace App\Actions\TechnicalMemories;

use App\Data\JudgmentCriterionData;
use App\Data\TechnicalMemoryGenerationContextData;
use App\Data\TechnicalMemorySectionData;
use App\Enums\TechnicalMemorySectionStatus;
use App\Jobs\GenerateTechnicalMemorySection;
use App\Models\TechnicalMemory;
use App\Models\TechnicalMemorySection;
use App\Models\Tender;

final class RegenerateSectionAction
{
    public function __invoke(TechnicalMemory $memory, TechnicalMemorySection $section): void
    {
        $tender = $memory->tender;

        $tender->loadMissing([
            'extractedCriteria',
            'extractedSpecifications',
            'pcaDocument',
            'pptDocument',
        ]);

        $sectionCriteria = $tender->extractedCriteria
            ->where('criterion_type', 'judgment')
            ->where('group_key', $section->group_key)
            ->values();

        $criteriaData = $sectionCriteria
            ->map(fn ($criterion): JudgmentCriterionData => JudgmentCriterionData::fromModel($criterion))
            ->all();

        $sectionData = new TechnicalMemorySectionData(
            groupKey: (string) $section->group_key,
            sectionNumber: $section->section_number,
            sectionTitle: (string) $section->section_title,
            totalPoints: (float) $section->total_points,
            criteriaCount: count($criteriaData),
            criteria: $criteriaData,
            sortKey: sprintf('%04d', (int) $section->sort_order),
        );

        $context = new TechnicalMemoryGenerationContextData(
            pca: [
                'criteria' => $this->buildPcaCriteriaPayload($tender),
                'insights' => $this->buildInsightsPayload($tender, optional($tender->pcaDocument)->id),
            ],
            ppt: [
                'specifications' => $this->buildSpecificationsPayload($tender),
                'insights' => $this->buildInsightsPayload($tender, optional($tender->pptDocument)->id),
            ],
            memoryTitle: (string) $memory->title,
        );

        $memory->update([
            'status' => 'draft',
            'generated_at' => null,
        ]);

        $section->update([
            'status' => TechnicalMemorySectionStatus::Pending,
            'error_message' => null,
            'content' => null,
        ]);

        GenerateTechnicalMemorySection::dispatch(
            technicalMemorySectionId: $section->id,
            section: $sectionData,
            context: $context,
        );
    }

    /**
     * @return array<int,array{section_number:?string,section_title:string,description:string,priority:string,criterion_type:string,score_points:?float,group_key:string,metadata:?array<string,mixed>}>
     */
    private function buildPcaCriteriaPayload(Tender $tender): array
    {
        return $tender->extractedCriteria
            ->where('criterion_type', 'judgment')
            ->values()
            ->map(fn ($criterion): array => JudgmentCriterionData::fromModel($criterion)->toArray())
            ->all();
    }

    /**
     * @return array<int,array<string,mixed>>
     */
    private function buildInsightsPayload(Tender $tender, ?int $documentId): array
    {
        if (! $documentId) {
            return [];
        }

        return $tender->documentInsights()
            ->where('document_id', $documentId)
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
            ->all();
    }

    /**
     * @return array<int,array<string,mixed>>
     */
    private function buildSpecificationsPayload(Tender $tender): array
    {
        return $tender->extractedSpecifications
            ->sortBy('id')
            ->values()
            ->map(fn ($item): array => [
                'section_number' => $item->section_number,
                'section_title' => $item->section_title,
                'technical_description' => $item->technical_description,
                'requirements' => $item->requirements,
                'deliverables' => $item->deliverables,
                'metadata' => $item->metadata,
            ])
            ->all();
    }
}
