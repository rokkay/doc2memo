<?php

namespace App\Ai\Agents;

use Laravel\Ai\Attributes\Model;
use Laravel\Ai\Attributes\Provider;
use Laravel\Ai\Attributes\Timeout;

#[Provider('openai')]
#[Model('gpt-5-mini')]
#[Timeout(300)]
class TechnicalMemoryTimelineAgent extends TechnicalMemorySectionAgent
{
    public function sectionField(): string
    {
        return 'timeline';
    }

    protected function sectionTitle(): string
    {
        return 'Cronograma';
    }

    protected function sectionObjective(): string
    {
        return 'Redacta un cronograma narrativo y un plan estructurado semanal con tareas dependientes, carriles de trabajo y hitos realistas.';
    }

    protected function requiresTimelinePlan(): bool
    {
        return true;
    }
}
