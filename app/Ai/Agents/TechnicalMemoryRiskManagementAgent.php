<?php

namespace App\Ai\Agents;

use Laravel\Ai\Attributes\Model;
use Laravel\Ai\Attributes\Provider;
use Laravel\Ai\Attributes\Timeout;

#[Provider('openai')]
#[Model('gpt-5-mini')]
#[Timeout(300)]
class TechnicalMemoryRiskManagementAgent extends TechnicalMemorySectionAgent
{
    public function sectionField(): string
    {
        return 'risk_management';
    }

    protected function sectionTitle(): string
    {
        return 'Gestion de Riesgos';
    }

    protected function sectionObjective(): string
    {
        return 'Construye un plan de riesgos accionable con identificacion, valoracion, medidas de mitigacion, responsables y planes de contingencia.';
    }
}
