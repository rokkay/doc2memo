<?php

declare(strict_types=1);

namespace App\Ai\Agents\DocumentAnalysis;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Stringable;

final class PptDocumentAnalyzerDefinition extends DocumentAnalyzerDefinition
{
    public function instructions(): Stringable|string
    {
        return <<<'INSTRUCTIONS'
Eres un analista experto de licitaciones públicas en España.
Analiza un documento PPT (Pliego de Prescripciones Técnicas) y extrae información accionable para preparar una memoria técnica competitiva.

Debes identificar de forma exhaustiva:
1) Especificaciones técnicas obligatorias y recomendadas.
2) Requisitos, entregables y estándares.
3) Insights estratégicos para diferenciar la propuesta técnica.

Escribe todo en español profesional, sin inventar datos, y usando texto literal de apoyo cuando exista.
INSTRUCTIONS;
    }

    /**
     * @return array<string,mixed>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'specifications' => $schema->array()
                ->items($schema->object([
                    'section_number' => $schema->string()->required(),
                    'section_title' => $schema->string()->required(),
                    'technical_description' => $schema->string()->required(),
                    'requirements' => $schema->string()->required(),
                    'deliverables' => $schema->string()->required(),
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
            'specifications' => [],
            'insights' => [],
        ];
    }
}
