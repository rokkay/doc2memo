<?php

namespace App\Ai\Agents;

use Laravel\Ai\Attributes\Model;
use Laravel\Ai\Attributes\Provider;
use Laravel\Ai\Attributes\Timeout;

#[Provider('openai')]
#[Model('gpt-5-mini')]
#[Timeout(300)]
class TechnicalMemoryMethodologyAgent extends TechnicalMemorySectionAgent
{
    public function sectionField(): string
    {
        return 'methodology';
    }

    protected function sectionTitle(): string
    {
        return 'Metodologia';
    }

    protected function sectionObjective(): string
    {
        return 'Explica metodologia de ejecucion por fases, modelo de gobernanza, hitos de seguimiento, ciclos de validacion y comunicacion con el organismo contratante.';
    }
}
