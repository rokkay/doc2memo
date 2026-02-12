<?php

namespace App\Jobs;

use App\Models\TechnicalMemory;
use App\Support\TechnicalMemorySections;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class GenerateTechnicalMemorySection implements ShouldQueue
{
    use Queueable;

    /**
     * @param  array<string,mixed>  $pcaData
     * @param  array<string,mixed>  $pptData
     */
    public function __construct(
        public int $technicalMemoryId,
        public string $section,
        public array $pcaData,
        public array $pptData,
    ) {}

    public function handle(): void
    {
        if (! TechnicalMemorySections::isSupported($this->section)) {
            return;
        }

        $memory = TechnicalMemory::query()->find($this->technicalMemoryId);

        if (! $memory) {
            return;
        }

        $agentClass = TechnicalMemorySections::agentClass($this->section);
        $sectionData = (new $agentClass($this->pcaData, $this->pptData))->generate();

        $memory->update([
            $this->section => $sectionData['content'],
            'timeline_plan' => $this->section === 'timeline'
                ? $sectionData['timeline_plan']
                : $memory->timeline_plan,
        ]);

        $memory = $memory->fresh();

        if (! $memory || $memory->status !== 'draft') {
            return;
        }

        if (TechnicalMemorySections::completedCount($memory) < count(TechnicalMemorySections::fields())) {
            return;
        }

        $memory->update([
            'full_report_markdown' => TechnicalMemorySections::buildFullReportMarkdown($memory),
            'status' => 'generated',
            'generated_at' => now(),
        ]);
    }
}
