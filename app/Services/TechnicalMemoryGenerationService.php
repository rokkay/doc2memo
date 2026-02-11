<?php

namespace App\Services;

use App\Actions\Tenders\GenerateTechnicalMemoryAction;
use App\Models\TechnicalMemory;
use App\Models\Tender;

class TechnicalMemoryGenerationService
{
    public function __construct(private readonly GenerateTechnicalMemoryAction $generateTechnicalMemoryAction) {}

    public function generate(Tender $tender): TechnicalMemory
    {
        ($this->generateTechnicalMemoryAction)($tender->fresh(['pcaDocument', 'pptDocument']));

        return TechnicalMemory::query()->where('tender_id', $tender->id)->firstOrFail();
    }
}
