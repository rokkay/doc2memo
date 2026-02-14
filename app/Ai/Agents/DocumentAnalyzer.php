<?php

namespace App\Ai\Agents;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Attributes\Model;
use Laravel\Ai\Attributes\Provider;
use Laravel\Ai\Attributes\Timeout;
use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Contracts\HasStructuredOutput;
use Laravel\Ai\Promptable;
use Stringable;

#[Provider('openai')]
#[Model('gpt-5.2')]
#[Timeout(300)]
class DocumentAnalyzer implements Agent, HasStructuredOutput
{
    use Promptable;

    public function __construct(private string $documentType = 'pca') {}

    public function instructions(): Stringable|string
    {
        if ($this->documentType === 'pca') {
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

    public function schema(JsonSchema $schema): array
    {
        if ($this->documentType === 'pca') {
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

    public function analyze(string $content): array
    {
        $response = $this->prompt($content);

        if ($this->documentType === 'pca') {
            return [
                'tender_info' => $this->value($response, 'tender_info', []),
                'criteria' => $this->value($response, 'criteria', []),
                'insights' => $this->value($response, 'insights', []),
            ];
        }

        return [
            'specifications' => $this->value($response, 'specifications', []),
            'insights' => $this->value($response, 'insights', []),
        ];
    }

    private function value(mixed $response, string $key, mixed $default): mixed
    {
        if (is_array($response)) {
            return $response[$key] ?? $default;
        }

        if ($response instanceof \ArrayAccess) {
            return $response[$key] ?? $default;
        }

        return $default;
    }
}
