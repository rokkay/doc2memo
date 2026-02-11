<?php

namespace App\Jobs;

use App\Actions\Tenders\GenerateTechnicalMemoryAction;
use App\Models\Tender;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class GenerateTechnicalMemory implements ShouldQueue
{
    use Queueable;

    public function __construct(public Tender $tender) {}

    public function handle(?GenerateTechnicalMemoryAction $generateTechnicalMemoryAction = null): void
    {
        $generateTechnicalMemoryAction ??= resolve(GenerateTechnicalMemoryAction::class);

        $generateTechnicalMemoryAction($this->tender->fresh(['pcaDocument', 'pptDocument']));
    }
}
