<?php

declare(strict_types=1);

use App\Ai\Agents\DocumentAnalysis\DocumentAnalyzerDefinition;
use App\Ai\Agents\PcaJudgmentCriteriaExtractorAgent;
use App\Ai\Agents\TechnicalMemoryDynamicSectionAgent;
use App\Ai\Agents\TechnicalMemorySectionEditorAgent;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Responses\AgentResponse;
use Laravel\Ai\Responses\Data\Meta;
use Laravel\Ai\Responses\Data\Usage;
use Laravel\Ai\Responses\StructuredAgentResponse;
use Tests\TestCase;

uses(TestCase::class);

it('normalizes analyzer definition responses for arrays and defaults', function (): void {
    $definition = new class extends DocumentAnalyzerDefinition
    {
        public function instructions(): string
        {
            return 'test';
        }

        public function schema(JsonSchema $schema): array
        {
            return [];
        }

        protected function outputDefaults(): array
        {
            return [
                'title' => '',
                'criteria' => [],
            ];
        }
    };

    expect($definition->normalizeResponse(['title' => 'Contrato']))->toBe([
        'title' => 'Contrato',
        'criteria' => [],
    ]);

    expect($definition->normalizeResponse('invalid'))->toBe([
        'title' => '',
        'criteria' => [],
    ]);
});

it('returns empty criteria when extractor prompt is unstructured', function (): void {
    $agent = new class extends PcaJudgmentCriteriaExtractorAgent
    {
        public function prompt(string $prompt, array $attachments = [], \Laravel\Ai\Enums\Lab|array|string|null $provider = null, ?string $model = null, ?int $timeout = null): AgentResponse
        {
            return new AgentResponse('invocation', 'plain text', new Usage, new Meta);
        }
    };

    expect($agent->extract('contenido'))->toBe([]);
});

it('returns criteria array from structured extractor response', function (): void {
    PcaJudgmentCriteriaExtractorAgent::fake([
        [
            'criteria' => [
                ['section_number' => '1.1', 'section_title' => 'Titulo', 'description' => 'Desc', 'priority' => 'mandatory', 'score_points' => '4', 'metadata' => []],
            ],
        ],
    ])->preventStrayPrompts();

    $criteria = (new PcaJudgmentCriteriaExtractorAgent)->extract('contenido');

    expect($criteria)->toHaveCount(1)
        ->and($criteria[0]['section_number'])->toBe('1.1');
});

it('reads structured content from dynamic section and editor agents', function (): void {
    $dynamicAgent = new class(['section_title' => 'S'], ['memory_title' => 'M']) extends TechnicalMemoryDynamicSectionAgent
    {
        public function prompt(string $prompt, array $attachments = [], \Laravel\Ai\Enums\Lab|array|string|null $provider = null, ?string $model = null, ?int $timeout = null): \Laravel\Ai\Responses\StructuredAgentResponse
        {
            return new StructuredAgentResponse('invocation', ['content' => "\x00### Bloque\n\nTexto"], 'json', new Usage, new Meta);
        }
    };

    $editorAgent = new class(['section_title' => 'S']) extends TechnicalMemorySectionEditorAgent
    {
        public function prompt(string $prompt, array $attachments = [], \Laravel\Ai\Enums\Lab|array|string|null $provider = null, ?string $model = null, ?int $timeout = null): \Laravel\Ai\Responses\StructuredAgentResponse
        {
            return new StructuredAgentResponse('invocation', ['content' => "\x7F### Editado\n\nTexto"], 'json', new Usage, new Meta);
        }
    };

    expect($dynamicAgent->generate())->toBe("### Bloque\n\nTexto")
        ->and($editorAgent->edit('base'))->toBe("### Editado\n\nTexto");
});
