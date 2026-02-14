<?php

declare(strict_types=1);

namespace App\Actions\Tenders;

use App\Data\JudgmentCriterionData;
use App\Data\TechnicalMemoryGenerationContextData;
use App\Data\TechnicalMemorySectionData;
use App\Enums\TechnicalMemorySectionStatus;
use App\Jobs\GenerateTechnicalMemorySection;
use App\Models\ExtractedCriterion;
use App\Models\TechnicalMemorySection;
use App\Models\Tender;
use App\Support\JudgmentCriteriaParser;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

final class GenerateTechnicalMemoryAction
{
    private JudgmentCriteriaParser $judgmentCriteriaParser;

    public function __construct(?JudgmentCriteriaParser $judgmentCriteriaParser = null)
    {
        $this->judgmentCriteriaParser = $judgmentCriteriaParser ?? new JudgmentCriteriaParser;
    }

    public function __invoke(Tender $tender): void
    {
        $rawJudgmentCriteria = $tender->judgmentCriteria()
            ->orderBy('id')
            ->get();

        $criteria = $this->expandCriteriaForGrouping($rawJudgmentCriteria);

        $sectionGroups = $this->buildSectionGroups($criteria);

        $pcaData = [
            'criteria' => $criteria
                ->map(fn (JudgmentCriterionData $item): array => $item->toArray())
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

        $generationContext = new TechnicalMemoryGenerationContextData(
            pca: $pcaData,
            ppt: $pptData,
            memoryTitle: (string) ('Memoria Técnica - '.$tender->title),
            runId: (string) Str::uuid(),
        );

        $memory = $tender->technicalMemory()->updateOrCreate(
            ['tender_id' => $tender->id],
            [
                'title' => 'Memoria Técnica - '.$tender->title,
                'status' => 'draft',
                'generated_file_path' => null,
                'generated_at' => null,
            ]
        );

        $memory->sections()->delete();

        if ($sectionGroups->isEmpty()) {
            $memory->update([
                'status' => 'generated',
                'generated_at' => now(),
            ]);

            return;
        }

        $totalPoints = (float) $sectionGroups->sum(fn (TechnicalMemorySectionData $group): float => $group->totalPoints);

        foreach ($sectionGroups->values() as $index => $group) {
            /** @var TechnicalMemorySectionData $group */
            $weightPercent = $totalPoints > 0
                ? round(($group->totalPoints / $totalPoints) * 100, 2)
                : 0.0;

            $section = TechnicalMemorySection::query()->create([
                'technical_memory_id' => $memory->id,
                'group_key' => $group->groupKey,
                'section_number' => $group->sectionNumber,
                'section_title' => $group->sectionTitle,
                'total_points' => round($group->totalPoints, 2),
                'weight_percent' => $weightPercent,
                'criteria_count' => $group->criteriaCount,
                'sort_order' => $index + 1,
                'status' => TechnicalMemorySectionStatus::Pending,
            ]);

            GenerateTechnicalMemorySection::dispatch(
                technicalMemorySectionId: $section->id,
                section: $group,
                context: $generationContext,
            );
        }
    }

    /**
     * @param  Collection<int,JudgmentCriterionData>  $criteria
     * @return Collection<int,TechnicalMemorySectionData>
     */
    private function buildSectionGroups(Collection $criteria): Collection
    {
        return $criteria
            ->groupBy(fn (JudgmentCriterionData $criterion): string => $this->groupKey($criterion))
            ->map(function (Collection $items, string $groupKey): TechnicalMemorySectionData {
                /** @var JudgmentCriterionData $first */
                $first = $items->first();

                $sectionNumber = (string) ($first->sectionNumber ?? '');
                $sectionTitle = trim($first->sectionTitle);

                return new TechnicalMemorySectionData(
                    groupKey: $groupKey,
                    sectionNumber: $sectionNumber !== '' ? $sectionNumber : null,
                    sectionTitle: $sectionTitle !== '' ? $sectionTitle : 'Sin sección',
                    totalPoints: (float) $items->sum(fn (JudgmentCriterionData $item): float => $item->scorePoints ?? 0.0),
                    criteriaCount: $items->count(),
                    criteria: $items->values()->all(),
                    sortKey: $this->sortKey($sectionNumber, $sectionTitle),
                );
            })
            ->sortBy(fn (TechnicalMemorySectionData $group): string => $group->sortKey)
            ->values();
    }

    private function groupKey(JudgmentCriterionData $criterion): string
    {
        $stored = trim($criterion->groupKey);

        if ($stored !== '') {
            return $stored;
        }

        return $this->judgmentCriteriaParser->buildGroupKey(
            $criterion->sectionNumber,
            $criterion->sectionTitle,
        );
    }

    /**
     * @return Collection<int,JudgmentCriterionData>
     */
    private function expandCriteriaForGrouping(Collection $criteria): Collection
    {
        return $criteria->flatMap(function (ExtractedCriterion $criterion): array {
            $criterionData = JudgmentCriterionData::fromModel($criterion);

            if ($this->judgmentCriteriaParser->hasExplicitSubcriterionNumber($criterionData->sectionNumber)) {
                return [$criterionData];
            }

            $subcriteria = $this->judgmentCriteriaParser->expandGroupedJudgmentCriterion(
                description: (string) $criterion->description,
                totalJudgmentPoints: $criterion->score_points !== null ? (float) $criterion->score_points : null,
            );

            if ($subcriteria === []) {
                return [$criterionData];
            }

            return collect($subcriteria)
                ->map(function (array $subcriterion) use ($criterionData): JudgmentCriterionData {
                    $sectionNumber = $subcriterion['section_number'];
                    $sectionTitle = $subcriterion['section_title'];
                    $scorePoints = $subcriterion['score_points'];

                    $normalizedNumber = $sectionNumber !== '' ? $sectionNumber : $criterionData->sectionNumber;
                    $normalizedTitle = $sectionTitle !== '' ? $sectionTitle : $criterionData->sectionTitle;

                    return JudgmentCriterionData::fromArray([
                        'section_number' => $normalizedNumber,
                        'section_title' => $normalizedTitle,
                        'description' => $normalizedTitle !== '' ? $normalizedTitle : $criterionData->description,
                        'priority' => $criterionData->priority,
                        'criterion_type' => $criterionData->criterionType,
                        'score_points' => $scorePoints,
                        'group_key' => $this->judgmentCriteriaParser->buildGroupKey($normalizedNumber, $normalizedTitle),
                        'source' => 'parser',
                        'confidence' => 0.65,
                        'source_reference' => $criterionData->sourceReference,
                        'metadata' => $criterionData->metadata,
                    ]);
                })
                ->values()
                ->all();
        })->values();
    }

    private function sortKey(?string $sectionNumber, string $sectionTitle): string
    {
        $number = trim((string) $sectionNumber);

        if ($number !== '') {
            $segments = collect(explode('.', $number))
                ->map(function (string $segment): string {
                    $numeric = preg_replace('/[^0-9]/', '', $segment) ?? '';

                    return str_pad($numeric !== '' ? $numeric : '0', 4, '0', STR_PAD_LEFT);
                })
                ->implode('.');

            return $segments.'|'.Str::lower($sectionTitle);
        }

        return '9999|'.Str::lower($sectionTitle);
    }
}
