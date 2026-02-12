<?php

namespace App\Ai\Agents;

use Laravel\Ai\Attributes\Model;
use Laravel\Ai\Attributes\Provider;
use Laravel\Ai\Attributes\Timeout;

#[Provider('openai')]
#[Model('gpt-5-mini')]
#[Timeout(300)]
class TechnicalMemoryTechnicalApproachAgent extends TechnicalMemorySectionAgent
{
    public function sectionField(): string
    {
        return 'technical_approach';
    }

    protected function sectionTitle(): string
    {
        return 'Enfoque Técnico';
    }

    protected function sectionObjective(): string
    {
        return 'Define la solución técnica propuesta con decisiones concretas de arquitectura, seguridad, integración, rendimiento y escalabilidad.';
    }
}
