<?php

namespace App\Ai\Agents;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Contracts\HasStructuredOutput;
use Laravel\Ai\Promptable;
use Stringable;

abstract class TechnicalMemorySectionAgent implements Agent, HasStructuredOutput
{
    use Promptable;

    /**
     * @param  array<string,mixed>  $pcaData
     * @param  array<string,mixed>  $pptData
     */
    public function __construct(
        protected array $pcaData = [],
        protected array $pptData = [],
    ) {}

    abstract public function sectionField(): string;

    abstract protected function sectionTitle(): string;

    abstract protected function sectionObjective(): string;

    protected function requiresTimelinePlan(): bool
    {
        return false;
    }

    public function instructions(): Stringable|string
    {
        return <<<'INSTRUCTIONS'
Eres un redactor senior de memorias técnicas para licitaciones públicas en España.
Escribe contenido formal, preciso y orientado a maximizar puntuación.

Debes:
- Responder solo con información sustentada en los datos de entrada.
- Evitar relleno, vaguedades y afirmaciones genéricas.
- Incluir medidas verificables, entregables y criterios claros cuando aplique.
- Mantener coherencia con los criterios administrativos y técnicos detectados.
- Desarrollar narrativa profunda en español profesional (no listas breves sin contexto).
- Explicar por qué cada decisión técnica aporta valor y cómo será evaluada.
- Incorporar de forma explícita los puntos de evaluación de criterios cuando existan.
INSTRUCTIONS;
    }

    public function schema(JsonSchema $schema): array
    {
        if ($this->requiresTimelinePlan()) {
            return [
                'timeline' => $schema->string()->required(),
                'timeline_plan' => $schema->object([
                    'total_weeks' => $schema->integer()->required(),
                    'tasks' => $schema->array()
                        ->items($schema->object([
                            'id' => $schema->string()->required(),
                            'title' => $schema->string()->required(),
                            'lane' => $schema->string()->required(),
                            'start_week' => $schema->integer()->required(),
                            'end_week' => $schema->integer()->required(),
                            'depends_on' => $schema->array()->items($schema->string())->required(),
                        ])->withoutAdditionalProperties())
                        ->required(),
                    'milestones' => $schema->array()
                        ->items($schema->object([
                            'title' => $schema->string()->required(),
                            'week' => $schema->integer()->required(),
                        ])->withoutAdditionalProperties())
                        ->required(),
                ])->required()->withoutAdditionalProperties(),
            ];
        }

        return [
            $this->sectionField() => $schema->string()->required(),
        ];
    }

    /**
     * @return array{content:string,timeline_plan:array{total_weeks:int,tasks:array<int,array{id:string,title:string,lane:string,start_week:int,end_week:int,depends_on:array<int,string>}>,milestones:array<int,array{title:string,week:int}>}|null}
     */
    public function generate(): array
    {
        $response = $this->prompt($this->buildPrompt());

        if ($this->requiresTimelinePlan()) {
            return [
                'content' => $this->sanitizeMarkdown((string) $this->value($response, 'timeline', '')),
                'timeline_plan' => $this->normalizeTimelinePlan($this->value($response, 'timeline_plan', [])),
            ];
        }

        return [
            'content' => $this->sanitizeMarkdown((string) $this->value($response, $this->sectionField(), '')),
            'timeline_plan' => null,
        ];
    }

    private function buildPrompt(): string
    {
        $pcaCriteria = json_encode($this->pcaData, JSON_PRETTY_PRINT);
        $pptSpecs = json_encode($this->pptData, JSON_PRETTY_PRINT);

        $outputRequirement = $this->requiresTimelinePlan()
            ? 'Return exactly two fields: "timeline" and "timeline_plan" following the schema.'
            : 'Return exactly one field: "'.$this->sectionField().'".';

        return <<<PROMPT
Generate only this section of a technical memory (Memoria Técnica): {$this->sectionTitle()}

## PCA CRITERIA (Pliego de Condiciones Administrativas):
{$pcaCriteria}

## PPT SPECIFICATIONS (Pliego de Prescripciones Tecnicas):
{$pptSpecs}

## INSIGHTS DE VALOR (PCA + PPT):
{$this->buildInsights()}

## EVALUATION CRITERIA POINTS (PCA):
{$this->buildEvaluationPoints()}

## SECTION OBJECTIVE
{$this->sectionObjective()}

## WRITING REQUIREMENTS
- Write a rich narrative with concrete details and clear rationale.
- Prefer 4-7 substantial paragraphs with smooth transitions.
- When criteria points are available, reference them explicitly and explain compliance strategy.
- Include measurable commitments, verification mechanisms, and expected impact.
- Return the section content as valid Markdown (not HTML).
- Do not include a top-level report title or a repeated section title using # or ##.
- Use Markdown structure to improve readability: short subsections (###), bullet lists, and tables when useful.
- Keep prose concise and scannable while preserving technical depth.

{$outputRequirement}
PROMPT;
    }

    private function buildInsights(): string
    {
        $insights = array_merge($this->pcaData['insights'] ?? [], $this->pptData['insights'] ?? []);

        return json_encode($insights, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) ?: '[]';
    }

    private function buildEvaluationPoints(): string
    {
        $points = $this->pcaData['evaluation_points'] ?? [];

        return json_encode($points, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) ?: '[]';
    }

    protected function value(mixed $response, string $key, mixed $default): mixed
    {
        if (is_array($response)) {
            return $response[$key] ?? $default;
        }

        if ($response instanceof \ArrayAccess) {
            return $response[$key] ?? $default;
        }

        return $default;
    }

    /**
     * @return array{total_weeks:int,tasks:array<int,array{id:string,title:string,lane:string,start_week:int,end_week:int,depends_on:array<int,string>}>,milestones:array<int,array{title:string,week:int}>}
     */
    protected function normalizeTimelinePlan(mixed $value): array
    {
        if (! is_array($value)) {
            return ['total_weeks' => 0, 'tasks' => [], 'milestones' => []];
        }

        $tasks = collect($value['tasks'] ?? [])
            ->filter(fn (mixed $task): bool => is_array($task))
            ->map(function (array $task): array {
                $startWeek = max(1, (int) ($task['start_week'] ?? 1));
                $endWeek = max($startWeek, (int) ($task['end_week'] ?? $startWeek));

                return [
                    'id' => (string) ($task['id'] ?? ''),
                    'title' => $this->sanitizeMarkdown((string) ($task['title'] ?? '')),
                    'lane' => $this->sanitizeMarkdown((string) ($task['lane'] ?? 'General')),
                    'start_week' => $startWeek,
                    'end_week' => $endWeek,
                    'depends_on' => collect($task['depends_on'] ?? [])
                        ->map(fn (mixed $dependency): string => (string) $dependency)
                        ->filter(fn (string $dependency): bool => $dependency !== '')
                        ->values()
                        ->all(),
                ];
            })
            ->filter(fn (array $task): bool => $task['id'] !== '' && $task['title'] !== '')
            ->values()
            ->all();

        $milestones = collect($value['milestones'] ?? [])
            ->filter(fn (mixed $milestone): bool => is_array($milestone))
            ->map(fn (array $milestone): array => [
                'title' => $this->sanitizeMarkdown((string) ($milestone['title'] ?? '')),
                'week' => max(1, (int) ($milestone['week'] ?? 1)),
            ])
            ->filter(fn (array $milestone): bool => $milestone['title'] !== '')
            ->values()
            ->all();

        $lastTaskWeek = collect($tasks)->max('end_week') ?? 0;
        $lastMilestoneWeek = collect($milestones)->max('week') ?? 0;

        return [
            'total_weeks' => max((int) ($value['total_weeks'] ?? 0), (int) $lastTaskWeek, (int) $lastMilestoneWeek),
            'tasks' => $tasks,
            'milestones' => $milestones,
        ];
    }

    protected function sanitizeMarkdown(string $content): string
    {
        $content = str_replace("\x13", 'ó', $content);

        $sanitized = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/', '', $content);

        return trim($sanitized ?? $content);
    }
}
