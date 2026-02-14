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
#[Model('gpt-5-mini')]
#[Timeout(300)]
class TechnicalMemoryDynamicSectionAgent implements Agent, HasStructuredOutput
{
    use Promptable;

    public const string MODEL_NAME = 'gpt-5-mini';

    /**
     * @param  array<string,mixed>  $section
     * @param  array<string,mixed>  $context
     */
    public function __construct(
        private array $section,
        private array $context,
    ) {}

    public function instructions(): Stringable|string
    {
        return <<<'INSTRUCTIONS'
Eres un redactor senior de memorias técnicas para licitaciones públicas en España.
Generas únicamente la sección solicitada y priorizas el contenido evaluable para maximizar la puntuación en criterios de juicio de valor.

Reglas:
- Usa solo la información proporcionada.
- Escribe en español profesional, concreto y verificable.
- Incluye compromisos medibles, evidencias y enfoque de cumplimiento.
- Desarrolla la sección con subsecciones claras en Markdown (`###`).
- Evita títulos globales del documento o prefijos irrelevantes del tipo "Criterios de adjudicación (B) Juicio de valor".
- Devuelve Markdown limpio y legible.
- No entregues resúmenes breves: desarrolla en profundidad homogénea con el resto de secciones.
INSTRUCTIONS;
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'content' => $schema->string()->required(),
        ];
    }

    public function generate(): string
    {
        $response = $this->prompt($this->promptText());

        $content = '';

        if (is_array($response)) {
            $content = (string) ($response['content'] ?? '');
        } elseif ($response instanceof \ArrayAccess) {
            $content = (string) ($response['content'] ?? '');
        }

        $sanitized = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/', '', $content);

        return trim($sanitized ?? $content);
    }

    public function modelName(): string
    {
        return self::MODEL_NAME;
    }

    public function estimateInputChars(): int
    {
        return mb_strlen($this->promptText());
    }

    private function promptText(): string
    {
        $sectionPayload = json_encode($this->section, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        $contextPayload = json_encode($this->context, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        $qualityFeedback = trim((string) ($this->context['quality_feedback'] ?? ''));
        $qualityFeedbackBlock = $qualityFeedback !== ''
            ? "## FEEDBACK DE CALIDAD A CORREGIR\n{$qualityFeedback}\n\n"
            : '';

        return <<<PROMPT
Genera el contenido de esta sección de memoria técnica.

## SECCION OBJETIVO
{$sectionPayload}

## CONTEXTO DE LICITACION
{$contextPayload}

{$qualityFeedbackBlock}## REQUISITOS DE REDACCION
- Entregar entre 8 y 14 párrafos sustanciales.
- Incluir al menos 3 subtítulos `###` relevantes para la sección.
- Añadir bullets operativos cuando aporten claridad en plan, evidencias y métricas.
- Referenciar explícitamente los criterios y su peso/puntos cuando existan.
- Explicar cómo la propuesta facilita la evaluación favorable en juicio de valor.
- No inventar datos ni incluir anexos ficticios.
- Devolver exclusivamente el campo `content` en Markdown.
PROMPT;
    }
}
