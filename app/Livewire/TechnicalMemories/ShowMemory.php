<?php

namespace App\Livewire\TechnicalMemories;

use App\Actions\TechnicalMemories\RegenerateSectionAction;
use App\Models\TechnicalMemorySection;
use App\Models\Tender;
use App\Support\SectionTitleNormalizer;
use App\ViewData\TechnicalMemoryViewData;
use Illuminate\View\View;
use Livewire\Component;

class ShowMemory extends Component
{
    private RegenerateSectionAction $regenerateSectionAction;

    public Tender $tender;

    public string $criteriaPriorityFilter = 'all';

    /** @var array<int,array{section:string,priority:string,points:string}> */
    public array $matrixRows = [];

    public int $judgmentCriteriaCount = 0;

    public function boot(RegenerateSectionAction $regenerateSectionAction): void
    {
        $this->regenerateSectionAction = $regenerateSectionAction;
    }

    public function mount(Tender $tender): void
    {
        $this->tender = $tender;

        $this->refreshMemory();
    }

    public function refreshMemory(): void
    {
        $this->tender->refresh();
        $this->tender->load([
            'technicalMemory.sections',
            'extractedCriteria',
            'extractedSpecifications',
            'pcaDocument',
            'pptDocument',
        ]);

        $this->refreshCriteriaMatrix();
    }

    public function setCriteriaPriorityFilter(string $priority): void
    {
        if (! in_array($priority, ['all', 'mandatory', 'preferable', 'optional'], true)) {
            return;
        }

        $this->criteriaPriorityFilter = $priority;
        $this->refreshCriteriaMatrix();
    }

    private function refreshCriteriaMatrix(): void
    {
        $judgmentCriteria = $this->tender->extractedCriteria
            ->where('criterion_type', 'judgment')
            ->values();

        $this->judgmentCriteriaCount = $judgmentCriteria->count();

        $rows = $judgmentCriteria
            ->map(function ($criterion): array {
                $points = $criterion->score_points !== null
                    ? number_format((float) $criterion->score_points, 2, ',', '.')
                    : 'N/D';

                return [
                    'section' => SectionTitleNormalizer::heading(
                        $criterion->section_number,
                        (string) $criterion->section_title,
                    ),
                    'priority' => (string) $criterion->priority,
                    'points' => $points,
                ];
            });

        if ($this->criteriaPriorityFilter !== 'all') {
            $rows = $rows->where('priority', $this->criteriaPriorityFilter);
        }

        $this->matrixRows = $rows->values()->all();
    }

    public function regenerateSection(int $sectionId): void
    {
        $memory = $this->tender->technicalMemory;

        if (! $memory) {
            return;
        }

        /** @var TechnicalMemorySection|null $section */
        $section = $memory->sections()->whereKey($sectionId)->first();

        if (! $section) {
            return;
        }

        ($this->regenerateSectionAction)($memory, $section);

        $this->refreshMemory();
    }

    public function getViewDataProperty(): TechnicalMemoryViewData
    {
        return TechnicalMemoryViewData::fromTender($this->tender);
    }

    public function render(): View
    {
        return view('livewire.technical-memories.show-memory');
    }
}
