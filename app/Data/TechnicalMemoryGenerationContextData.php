<?php

declare(strict_types=1);

namespace App\Data;

final class TechnicalMemoryGenerationContextData
{
    /**
     * @param  array<string,mixed>  $pca
     * @param  array<string,mixed>  $ppt
     */
    public function __construct(
        public readonly array $pca,
        public readonly array $ppt,
        public readonly string $memoryTitle,
        public readonly ?string $qualityFeedback = null,
    ) {}

    /**
     * @param  array<string,mixed>  $payload
     */
    public static function fromArray(array $payload): self
    {
        return new self(
            pca: is_array($payload['pca'] ?? null) ? $payload['pca'] : [],
            ppt: is_array($payload['ppt'] ?? null) ? $payload['ppt'] : [],
            memoryTitle: (string) ($payload['memory_title'] ?? ''),
            qualityFeedback: isset($payload['quality_feedback']) ? (string) $payload['quality_feedback'] : null,
        );
    }

    public function withQualityFeedback(string $qualityFeedback): self
    {
        return new self(
            pca: $this->pca,
            ppt: $this->ppt,
            memoryTitle: $this->memoryTitle,
            qualityFeedback: $qualityFeedback,
        );
    }

    /**
     * @return array{pca:array<string,mixed>,ppt:array<string,mixed>,memory_title:string,quality_feedback:?string}
     */
    public function toArray(): array
    {
        return [
            'pca' => $this->pca,
            'ppt' => $this->ppt,
            'memory_title' => $this->memoryTitle,
            'quality_feedback' => $this->qualityFeedback,
        ];
    }
}
