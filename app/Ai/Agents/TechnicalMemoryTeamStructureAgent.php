<?php

namespace App\Ai\Agents;

use Laravel\Ai\Attributes\Model;
use Laravel\Ai\Attributes\Provider;
use Laravel\Ai\Attributes\Timeout;

#[Provider('openai')]
#[Model('gpt-5-mini')]
#[Timeout(300)]
class TechnicalMemoryTeamStructureAgent extends TechnicalMemorySectionAgent
{
    public function sectionField(): string
    {
        return 'team_structure';
    }

    protected function sectionTitle(): string
    {
        return 'Estructura del Equipo';
    }

    protected function sectionObjective(): string
    {
        return 'Describe estructura de equipo, roles clave, responsabilidades, cobertura funcional y mecanismo de sustitucion para continuidad operativa.';
    }
}
