<?php

namespace App\Ai\Agents;

use Laravel\Ai\Attributes\Model;
use Laravel\Ai\Attributes\Provider;
use Laravel\Ai\Attributes\Timeout;

#[Provider('openai')]
#[Model('gpt-5-mini')]
#[Timeout(300)]
class TechnicalMemoryComplianceMatrixAgent extends TechnicalMemorySectionAgent
{
    public function sectionField(): string
    {
        return 'compliance_matrix';
    }

    protected function sectionTitle(): string
    {
        return 'Matriz de Cumplimiento';
    }

    protected function sectionObjective(): string
    {
        return 'Redacta una matriz narrativa que mapee requisitos relevantes del pliego con la respuesta técnica y evidencias de cumplimiento.';
    }
}
