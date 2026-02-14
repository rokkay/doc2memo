<?php

declare(strict_types=1);

namespace App\Ai\Agents\DocumentAnalysis;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Stringable;

abstract class DocumentAnalyzerDefinition
{
    abstract public function instructions(): Stringable|string;

    /**
     * @return array<string,mixed>
     */
    abstract public function schema(JsonSchema $schema): array;

    /**
     * @return array<string,mixed>
     */
    final public function normalizeResponse(mixed $response): array
    {
        $normalized = [];

        foreach ($this->outputDefaults() as $key => $default) {
            $normalized[$key] = $this->value($response, $key, $default);
        }

        return $normalized;
    }

    /**
     * @return array<string,mixed>
     */
    abstract protected function outputDefaults(): array;

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
