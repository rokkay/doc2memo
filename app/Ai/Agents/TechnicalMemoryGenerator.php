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
}
