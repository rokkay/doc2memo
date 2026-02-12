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
        return 'Gestión de Riesgos';
    }

    protected function sectionObjective(): string
    {
        return 'Construye un plan de riesgos accionable con identificación, valoración, medidas de mitigación, responsables y planes de contingencia.';
    }
}
