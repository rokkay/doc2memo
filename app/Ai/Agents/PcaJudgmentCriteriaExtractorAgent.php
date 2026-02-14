<?php

declare(strict_types=1);

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
class PcaJudgmentCriteriaExtractorAgent implements Agent, HasStructuredOutput
{
    use Promptable;

    public const string MODEL_NAME = 'gpt-5.2';

    public function instructions(): Stringable|string
    {
        return <<<'INSTRUCTIONS'
Eres especialista en licitaciones públicas (España).
Tu tarea es extraer exclusivamente criterios de evaluación de JUICIO DE VALOR (Sobre B) desde un PCA.

Reglas:
- No extraigas condiciones administrativas, ejecución, penalidades ni compliance legal salvo que sean criterios evaluables de juicio de valor.
- Si hay tabla o cuadro de criterios, prioriza esa fuente.
- Si hay bloques descriptivos sin numeración explícita, extrae subcriterios independientes con títulos claros.
- Devuelve una fila por subcriterio evaluable.
- Usa score_points en texto numérico (por ejemplo "16", "4.5"); si no hay puntuación explícita, usa "".
- No inventes puntuaciones.
INSTRUCTIONS;
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'criteria' => $schema->array()
                ->items($schema->object([
                    'section_number' => $schema->string()->required(),
                    'section_title' => $schema->string()->required(),
                    'description' => $schema->string()->required(),
                    'priority' => $schema->string()->enum(['mandatory', 'preferable', 'optional'])->required(),
                    'score_points' => $schema->string()->required(),
                    'metadata' => $schema->object()->withoutAdditionalProperties(),
                ])->withoutAdditionalProperties())
                ->required(),
        ];
    }

    /**
     * @return array<int,array{section_number:string,section_title:string,description:string,priority:string,score_points:string,metadata:array<string,mixed>}>
     */
    public function extract(string $content): array
    {
        $response = $this->prompt($content);

        if (is_array($response)) {
            return is_array($response['criteria'] ?? null) ? $response['criteria'] : [];
        }

        if ($response instanceof \ArrayAccess) {
            $criteria = $response['criteria'] ?? [];

            return is_array($criteria) ? $criteria : [];
        }

        return [];
    }

    public function modelName(): string
    {
        return self::MODEL_NAME;
    }

    public function estimateInputChars(string $content): int
    {
        return mb_strlen($content);
    }
}
