<?php

namespace App\Ai\Agents;

use Laravel\Ai\Attributes\Model;
use Laravel\Ai\Attributes\Provider;
use Laravel\Ai\Attributes\Timeout;

#[Provider('openai')]
#[Model('gpt-5-mini')]
#[Timeout(300)]
class TechnicalMemoryIntroductionAgent extends TechnicalMemorySectionAgent
{
    public function sectionField(): string
    {
        return 'introduction';
    }

    protected function sectionTitle(): string
    {
        return 'Introduccion';
    }

    protected function sectionObjective(): string
    {
        return 'Redacta una apertura persuasiva que contextualice la licitacion, el alcance del servicio y la propuesta de valor. Debe anticipar enfoque, cumplimiento y resultados medibles.';
    }
}
