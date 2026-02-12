<?php

namespace App\Ai\Agents;

use Laravel\Ai\Attributes\Model;
use Laravel\Ai\Attributes\Provider;
use Laravel\Ai\Attributes\Timeout;

#[Provider('openai')]
#[Model('gpt-5-mini')]
#[Timeout(300)]
class TechnicalMemoryQualityAssuranceAgent extends TechnicalMemorySectionAgent
{
    public function sectionField(): string
    {
        return 'quality_assurance';
    }

    protected function sectionTitle(): string
    {
        return 'Aseguramiento de Calidad';
    }

    protected function sectionObjective(): string
    {
        return 'Detalla estrategia de calidad con controles preventivos y correctivos, criterios de aceptacion, evidencias y metricas de seguimiento.';
    }
}
