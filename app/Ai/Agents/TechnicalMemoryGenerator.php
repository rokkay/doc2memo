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
class TechnicalMemoryGenerator implements Agent, HasStructuredOutput
{
    use Promptable;

    private array $pcaData;

    private array $pptData;

    public function __construct(array $pcaData = [], array $pptData = [])
    {
        $this->pcaData = $pcaData;
        $this->pptData = $pptData;
    }

    public function instructions(): Stringable|string
    {
        return <<<'INSTRUCTIONS'
Eres un redactor senior de memorias tecnicas para licitaciones publicas en Espana.
Genera una memoria tecnica convincente, clara y orientada a maximizar puntuacion.

Debes:
- Responder a los criterios administrativos y tecnicos detectados.
- Proponer una estrategia realista, medible y verificable.
- Mantener tono formal y lenguaje profesional en espanol.
- Evitar afirmaciones no sustentadas por los datos proporcionados.
- Construir un cronograma estructurado para visualizacion y futura exportacion.

Para el campo timeline_plan debes devolver un objeto JSON con:
- total_weeks: numero entero mayor o igual a 1
- tasks: lista de tareas con id, title, lane, start_week, end_week y depends_on
- milestones: lista de hitos con title y week

Reglas de timeline_plan:
- start_week y end_week deben ser enteros, con end_week >= start_week.
- depends_on contiene ids de tareas previas cuando haya dependencia.
- Usa semanas realistas y coherentes con la complejidad del proyecto.
INSTRUCTIONS;
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'title' => $schema->string()->required(),
            'introduction' => $schema->string()->required(),
            'company_presentation' => $schema->string()->required(),
            'technical_approach' => $schema->string()->required(),
            'methodology' => $schema->string()->required(),
            'team_structure' => $schema->string()->required(),
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
            'quality_assurance' => $schema->string()->required(),
            'risk_management' => $schema->string()->required(),
            'compliance_matrix' => $schema->string()->required(),
            'full_report_markdown' => $schema->string()->required(),
        ];
    }

    public function generate(): array
    {
        $prompt = $this->buildPrompt();

        $response = $this->prompt($prompt);

        return [
            'title' => $this->value($response, 'title', 'Memoria Tecnica'),
            'introduction' => $this->value($response, 'introduction', ''),
            'company_presentation' => $this->value($response, 'company_presentation', ''),
            'technical_approach' => $this->value($response, 'technical_approach', ''),
            'methodology' => $this->value($response, 'methodology', ''),
            'team_structure' => $this->value($response, 'team_structure', ''),
            'timeline' => $this->value($response, 'timeline', ''),
            'timeline_plan' => $this->normalizeTimelinePlan($this->value($response, 'timeline_plan', [])),
            'quality_assurance' => $this->value($response, 'quality_assurance', ''),
            'risk_management' => $this->value($response, 'risk_management', ''),
            'compliance_matrix' => $this->value($response, 'compliance_matrix', ''),
            'full_report_markdown' => $this->value($response, 'full_report_markdown', ''),
        ];
    }

    private function buildPrompt(): string
    {
        $pcaCriteria = json_encode($this->pcaData, JSON_PRETTY_PRINT);
        $pptSpecs = json_encode($this->pptData, JSON_PRETTY_PRINT);

        return <<<PROMPT
Based on the following tender information, generate a comprehensive technical memory (Memoria Técnica):

## PCA CRITERIA (Pliego de Condiciones Administrativas):
{$pcaCriteria}

## PPT SPECIFICATIONS (Pliego de Prescripciones Técnicas):
{$pptSpecs}

## INSIGHTS DE VALOR (PCA + PPT):
{$this->buildInsights()}

Generate a professional technical memory that addresses all administrative requirements using the technical specifications provided.
PROMPT;
    }

    private function buildInsights(): string
    {
        $insights = array_merge($this->pcaData['insights'] ?? [], $this->pptData['insights'] ?? []);

        return json_encode($insights, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) ?: '[]';
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

    /**
     * @return array{total_weeks:int,tasks:array<int,array{id:string,title:string,lane:string,start_week:int,end_week:int,depends_on:array<int,string>}>,milestones:array<int,array{title:string,week:int}>}
     */
    private function normalizeTimelinePlan(mixed $value): array
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
                    'title' => (string) ($task['title'] ?? ''),
                    'lane' => (string) ($task['lane'] ?? 'General'),
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
                'title' => (string) ($milestone['title'] ?? ''),
                'week' => max(1, (int) ($milestone['week'] ?? 1)),
            ])
            ->filter(fn (array $milestone): bool => $milestone['title'] !== '')
            ->values()
            ->all();

        $lastTaskWeek = collect($tasks)->max('end_week') ?? 0;
        $lastMilestoneWeek = collect($milestones)->max('week') ?? 0;

        $totalWeeks = max(
            (int) ($value['total_weeks'] ?? 0),
            (int) $lastTaskWeek,
            (int) $lastMilestoneWeek
        );

        return [
            'total_weeks' => $totalWeeks,
            'tasks' => $tasks,
            'milestones' => $milestones,
        ];
    }
}
