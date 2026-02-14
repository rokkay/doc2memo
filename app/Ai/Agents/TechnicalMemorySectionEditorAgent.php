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
#[Model('gpt-5-mini')]
#[Timeout(120)]
class TechnicalMemorySectionEditorAgent implements Agent, HasStructuredOutput
{
    use Promptable;

    public const string MODEL_NAME = 'gpt-5-mini';

    /**
     * @param  array<string,mixed>  $section
     */
    public function __construct(private array $section) {}

    public function instructions(): Stringable|string
    {
        return <<<'INSTRUCTIONS'
Eres editor senior de memorias técnicas para licitaciones públicas.
Tu tarea es mejorar el estilo de una sección YA redactada.

Reglas editoriales:
- Mantén el significado, datos, puntuaciones y compromisos.
- No inventes información nueva.
- Conserva estructura Markdown y subtítulos existentes.
- Mejora claridad, variedad de arranques y fluidez.
- El resultado debe sonar profesional y natural, no repetitivo.
INSTRUCTIONS;
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'content' => $schema->string()->required(),
        ];
    }

    public function edit(string $content): string
    {
        $response = $this->prompt($this->promptText($content));

        $editedContent = '';

        if (is_array($response)) {
            $editedContent = (string) ($response['content'] ?? '');
        } elseif ($response instanceof \ArrayAccess) {
            $editedContent = (string) ($response['content'] ?? '');
        }

        $sanitized = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/', '', $editedContent);

        return trim($sanitized ?? $editedContent);
    }

    public function modelName(): string
    {
        return self::MODEL_NAME;
    }

    public function estimateInputChars(string $content): int
    {
        return mb_strlen($this->promptText($content));
    }

    private function promptText(string $content): string
    {
        $sectionPayload = json_encode($this->section, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

        return <<<PROMPT
Edita esta sección para mejorar estilo sin cambiar contenido factual.

## SECCIÓN
{$sectionPayload}

## TEXTO A EDITAR
{$content}

## RESTRICCIONES
- Respeta títulos y subtítulos Markdown existentes.
- Mantén ideas y hechos; mejora redacción y cohesión.
- Evita repeticiones innecesarias y aperturas clónicas.
- Devuelve solo `content` en Markdown.
PROMPT;
    }
}
