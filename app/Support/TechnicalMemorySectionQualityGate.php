<?php

declare(strict_types=1);

namespace App\Support;

final class TechnicalMemorySectionQualityGate
{
    /**
     * @return array{passes:bool,reasons:array<int,string>}
     */
    public function evaluate(string $content, ?int $medianCompletedLength = null): array
    {
        $reasons = [];

        $charCount = mb_strlen(trim($content));
        $h3Count = preg_match_all('/^###\s+/m', $content) ?: 0;

        $baseMinChars = (int) config('technical_memory.quality_gate.min_chars', 1800);
        $relativeMinFactor = (float) config('technical_memory.quality_gate.relative_min_factor', 0.45);
        $relativeMinChars = $medianCompletedLength !== null && $medianCompletedLength > 0
            ? (int) round($medianCompletedLength * $relativeMinFactor)
            : 0;

        $requiredChars = max($baseMinChars, $relativeMinChars);
        $requiredH3 = (int) config('technical_memory.quality_gate.min_h3', 3);

        if ($charCount < $requiredChars) {
            $reasons[] = "Contenido insuficiente: {$charCount} caracteres; mínimo esperado {$requiredChars}.";
        }

        if ($h3Count < $requiredH3) {
            $reasons[] = "Estructura insuficiente: {$h3Count} subtítulos H3; mínimo esperado {$requiredH3}.";
        }

        return [
            'passes' => $reasons === [],
            'reasons' => $reasons,
        ];
    }
}
