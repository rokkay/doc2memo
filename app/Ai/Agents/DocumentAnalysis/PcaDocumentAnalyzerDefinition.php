<?php

declare(strict_types=1);

namespace App\Ai\Agents\DocumentAnalysis;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Stringable;

final class PcaDocumentAnalyzerDefinition extends DocumentAnalyzerDefinition
{
    public function instructions(): Stringable|string
    {
        return <<<'INSTRUCTIONS'
Eres un analista experto de licitaciones públicas en España.
Analiza un documento PCA (Pliego de Cláusulas Administrativas) y extrae información accionable para preparar una memoria técnica competitiva.

Debes identificar de forma exhaustiva:
1) Datos clave de la licitación.
2) Criterios y obligaciones administrativas.
3) Insights estratégicos para ganar puntos en evaluación.

Escribe todo en español profesional, sin inventar datos, y usando texto literal de apoyo cuando exista.
INSTRUCTIONS;
    }

    /**
     * @return array<string,mixed>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'tender_info' => $schema->object([
                'title' => $schema->string()->required(),
                'issuing_company' => $schema->string()->required(),
                'reference_number' => $schema->string()->required(),
                'deadline_date' => $schema->string()->required(),
                'description' => $schema->string()->required(),
            ])->required()->withoutAdditionalProperties(),
            'criteria' => $schema->array()
                ->items($schema->object([
                    'section_number' => $schema->string()->required(),
                    'section_title' => $schema->string()->required(),
                    'description' => $schema->string()->required(),
                    'priority' => $schema->string()->enum(['mandatory', 'preferable', 'optional'])->required(),
                    'criterion_type' => $schema->string()->enum(['judgment', 'automatic'])->required(),
                    'score_points' => $schema->string()->required(),
                    'metadata' => $schema->object()->withoutAdditionalProperties(),
                ])->withoutAdditionalProperties())
                ->required(),
            'insights' => $schema->array()
                ->items($schema->object([
                    'section_reference' => $schema->string()->required(),
                    'topic' => $schema->string()->required(),
                    'requirement_type' => $schema->string()->enum([
                        'administrative',
                        'technical',
                        'budget',
                        'timeline',
                        'deliverable',
                        'evaluation',
                        'compliance',
                        'risk',
                    ])->required(),
                    'importance' => $schema->string()->enum(['high', 'medium', 'low'])->required(),
                    'statement' => $schema->string()->required(),
                    'evidence_excerpt' => $schema->string()->required(),
                ])->withoutAdditionalProperties())
                ->required(),
        ];
    }

    /**
     * @return array<string,mixed>
     */
    protected function outputDefaults(): array
    {
        return [
            'tender_info' => [],
            'criteria' => [],
            'insights' => [],
        ];
    }
}
