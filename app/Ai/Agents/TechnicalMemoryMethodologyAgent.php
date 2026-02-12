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
        return 'Metodología';
    }

    protected function sectionObjective(): string
    {
        return 'Explica metodología de ejecución por fases, modelo de gobernanza, hitos de seguimiento, ciclos de validación y comunicación con el organismo contratante.';
    }
}
