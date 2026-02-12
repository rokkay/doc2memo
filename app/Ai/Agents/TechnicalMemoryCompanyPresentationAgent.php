<?php

namespace App\Ai\Agents;

use Laravel\Ai\Attributes\Model;
use Laravel\Ai\Attributes\Provider;
use Laravel\Ai\Attributes\Timeout;

#[Provider('openai')]
#[Model('gpt-5-mini')]
#[Timeout(300)]
class TechnicalMemoryCompanyPresentationAgent extends TechnicalMemorySectionAgent
{
    public function sectionField(): string
    {
        return 'company_presentation';
    }

    protected function sectionTitle(): string
    {
        return 'Presentación de la Empresa';
    }

    protected function sectionObjective(): string
    {
        return 'Presenta capacidades, experiencia relevante, certificaciones y activos diferenciales vinculados a los requisitos del expediente.';
    }
}
