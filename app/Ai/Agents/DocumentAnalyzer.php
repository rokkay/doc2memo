<?php

declare(strict_types=1);

namespace App\Ai\Agents;

use App\Ai\Agents\DocumentAnalysis\DocumentAnalyzerDefinition;
use App\Ai\Agents\DocumentAnalysis\PcaDocumentAnalyzerDefinition;
use App\Ai\Agents\DocumentAnalysis\PptDocumentAnalyzerDefinition;
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

    public const string MODEL_NAME = 'gpt-5.2';

    private readonly DocumentAnalyzerDefinition $definition;

    public function __construct(string $documentType = 'pca')
    {
        $this->definition = self::resolveDefinition($documentType);
    }

    public function instructions(): Stringable|string
    {
        return $this->definition->instructions();
    }

    public function schema(JsonSchema $schema): array
    {
        return $this->definition->schema($schema);
    }

    public function analyze(string $content): array
    {
        $response = $this->prompt($content);

        return $this->definition->normalizeResponse($response);
    }

    public function modelName(): string
    {
        return self::MODEL_NAME;
    }

    public function estimateInputChars(string $content): int
    {
        return mb_strlen($content);
    }

    private static function resolveDefinition(string $documentType): DocumentAnalyzerDefinition
    {
        return match ($documentType) {
            'pca' => new PcaDocumentAnalyzerDefinition,
            default => new PptDocumentAnalyzerDefinition,
        };
    }
}
