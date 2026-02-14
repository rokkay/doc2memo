<?php

namespace App\Livewire\TechnicalMemories;

use App\Actions\TechnicalMemories\RegenerateSectionAction;
use App\Models\ExtractedCriterion;
use App\Models\TechnicalMemorySection;
use App\Models\Tender;
use App\ViewData\TechnicalMemoryViewData;
use Illuminate\Support\Collection;
use Illuminate\View\View;
use Livewire\Component;

class ShowMemory extends Component
{
    private RegenerateSectionAction $regenerateSectionAction;

    public Tender $tender;

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

        $this->judgmentCriteriaCount = $this->preferredJudgmentCriteria()->count();
    }

    /**
     * @return Collection<int,ExtractedCriterion>
     */
    private function preferredJudgmentCriteria(): Collection
    {
        $judgmentCriteria = $this->tender->extractedCriteria
            ->where('criterion_type', 'judgment')
            ->values();

        $dedicatedCriteria = $judgmentCriteria
            ->where('source', 'dedicated_extractor')
            ->values();

        if ($dedicatedCriteria->isNotEmpty()) {
            return $dedicatedCriteria;
        }

        return $judgmentCriteria;
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
