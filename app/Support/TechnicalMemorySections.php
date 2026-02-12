<?php

namespace App\Support;

use App\Ai\Agents\TechnicalMemoryCompanyPresentationAgent;
use App\Ai\Agents\TechnicalMemoryComplianceMatrixAgent;
use App\Ai\Agents\TechnicalMemoryIntroductionAgent;
use App\Ai\Agents\TechnicalMemoryMethodologyAgent;
use App\Ai\Agents\TechnicalMemoryQualityAssuranceAgent;
use App\Ai\Agents\TechnicalMemoryRiskManagementAgent;
use App\Ai\Agents\TechnicalMemorySectionAgent;
use App\Ai\Agents\TechnicalMemoryTeamStructureAgent;
use App\Ai\Agents\TechnicalMemoryTechnicalApproachAgent;
use App\Ai\Agents\TechnicalMemoryTimelineAgent;
use App\Models\TechnicalMemory;

class TechnicalMemorySections
{
    /**
     * @return array<int,string>
     */
    public static function fields(): array
    {
        return [
            'introduction',
            'company_presentation',
            'technical_approach',
            'methodology',
            'team_structure',
            'timeline',
            'quality_assurance',
            'risk_management',
            'compliance_matrix',
        ];
    }

    public static function isSupported(string $field): bool
    {
        return in_array($field, self::fields(), true);
    }

    public static function title(string $field): string
    {
        return match ($field) {
            'introduction' => 'Introducción',
            'company_presentation' => 'Presentación de la Empresa',
            'technical_approach' => 'Enfoque Técnico',
            'methodology' => 'Metodología',
            'team_structure' => 'Estructura del Equipo',
            'timeline' => 'Cronograma',
            'quality_assurance' => 'Aseguramiento de Calidad',
            'risk_management' => 'Gestión de Riesgos',
            'compliance_matrix' => 'Matriz de Cumplimiento',
            default => $field,
        };
    }

    /**
     * @return class-string<TechnicalMemorySectionAgent>
     */
    public static function agentClass(string $field): string
    {
        return match ($field) {
            'introduction' => TechnicalMemoryIntroductionAgent::class,
            'company_presentation' => TechnicalMemoryCompanyPresentationAgent::class,
            'technical_approach' => TechnicalMemoryTechnicalApproachAgent::class,
            'methodology' => TechnicalMemoryMethodologyAgent::class,
            'team_structure' => TechnicalMemoryTeamStructureAgent::class,
            'timeline' => TechnicalMemoryTimelineAgent::class,
            'quality_assurance' => TechnicalMemoryQualityAssuranceAgent::class,
            'risk_management' => TechnicalMemoryRiskManagementAgent::class,
            'compliance_matrix' => TechnicalMemoryComplianceMatrixAgent::class,
            default => TechnicalMemoryIntroductionAgent::class,
        };
    }

    public static function completedCount(TechnicalMemory $memory): int
    {
        return collect(self::fields())
            ->filter(fn (string $field): bool => filled($memory->{$field}))
            ->count();
    }
}
