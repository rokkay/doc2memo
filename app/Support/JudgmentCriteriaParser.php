<?php

declare(strict_types=1);

namespace App\Support;

use Illuminate\Support\Str;

final class JudgmentCriteriaParser
{
    public function hasExplicitSubcriterionNumber(?string $sectionNumber): bool
    {
        if ($sectionNumber === null) {
            return false;
        }

        return preg_match('/^\d+\.\d+$/', trim($sectionNumber)) === 1;
    }

    /**
     * @return array<int,array{section_number:string,section_title:string,score_points:?float}>
     */
    public function expandGroupedJudgmentCriterion(string $description, ?float $totalJudgmentPoints = null): array
    {
        $normalizedDescription = str_replace(["\r\n", "\r"], ' ', $description);

        preg_match_all(
            '/(\d+\.\d+)\s+([^;:()]+)\s+(\d+(?:[\.,]\d+)?)(?=[;.)]|$)/u',
            $normalizedDescription,
            $matches,
            PREG_SET_ORDER,
        );

        if (count($matches) >= 2) {
            return collect($matches)
                ->map(function (array $match): array {
                    $sectionNumber = trim((string) ($match[1] ?? ''));
                    $sectionTitle = $this->formatSubcriterionTitle((string) ($match[2] ?? ''));
                    $scorePoints = $this->parseNumericValue($match[3] ?? null) ?? 0.0;

                    return [
                        'section_number' => $sectionNumber,
                        'section_title' => $sectionTitle,
                        'score_points' => $scorePoints,
                    ];
                })
                ->filter(fn (array $item): bool => $item['section_number'] !== '' && $item['section_title'] !== '')
                ->values()
                ->all();
        }

        return $this->expandDescriptiveJudgmentCriterion($normalizedDescription, $totalJudgmentPoints);
    }

    /**
     * @return array<int,array{section_number:string,section_title:string,score_points:?float}>
     */
    private function expandDescriptiveJudgmentCriterion(string $description, ?float $totalJudgmentPoints): array
    {
        $definitions = [
            ['number' => '1.1', 'title' => 'Propuesta de Evolución Funcional', 'pattern' => '/propuesta\s+funcional|evoluci[oó]n\s+funcional/iu'],
            ['number' => '1.2', 'title' => 'Propuesta de Evolución Tecnológica', 'pattern' => '/propuesta\s+tecnol[oó]gica|evoluci[oó]n\s+tecnol[oó]gica/iu'],
            ['number' => '1.3', 'title' => 'Plan de Ejecución del Contrato', 'pattern' => '/plan\s+ejecuci[oó]n|cronograma|hitos\s+y\s+entregables/iu'],
            ['number' => '2.1', 'title' => 'Metodología de Prestación', 'pattern' => '/metodolog[ií]a/iu'],
            ['number' => '2.2', 'title' => 'Organización y Gestión del Equipo', 'pattern' => '/organizaci[oó]n\s+y\s+gesti[oó]n\s+del\s+equipo|matriz\s+raci|perfiles/iu'],
            ['number' => '2.3', 'title' => 'Plan de Capacitación', 'pattern' => '/plan\s+de\s+formaci[oó]n|capacitaci[oó]n|formaci[oó]n\s+continuada/iu'],
            ['number' => '2.4', 'title' => 'Mecanismos de Seguimiento y Control', 'pattern' => '/seguimiento|control|devoluci[oó]n\s+del\s+servicio|transferencia\s+conocimiento/iu'],
        ];

        $matches = collect($definitions)
            ->filter(fn (array $definition): bool => preg_match($definition['pattern'], $description) === 1)
            ->values();

        if ($matches->count() < 2) {
            return [];
        }

        $scores = $this->resolveSemanticScores($matches->pluck('number')->all(), $totalJudgmentPoints);

        return $matches
            ->map(function (array $definition) use ($scores): array {
                return [
                    'section_number' => $definition['number'],
                    'section_title' => $definition['title'],
                    'score_points' => $scores[$definition['number']] ?? null,
                ];
            })
            ->all();
    }

    /**
     * @param  array<int,string>  $numbers
     * @return array<string,float|null>
     */
    private function resolveSemanticScores(array $numbers, ?float $totalJudgmentPoints): array
    {
        $defaultMap = [
            '1.1' => 16.0,
            '1.2' => 10.0,
            '1.3' => 4.0,
            '2.1' => 6.0,
            '2.2' => 8.0,
            '2.3' => 2.0,
            '2.4' => 4.0,
        ];

        $selected = collect($numbers)
            ->mapWithKeys(fn (string $number): array => [$number => $defaultMap[$number] ?? null])
            ->all();

        if ($totalJudgmentPoints === null) {
            return $selected;
        }

        $mappedTotal = collect($selected)
            ->filter(fn (?float $score): bool => $score !== null)
            ->sum();

        if ($mappedTotal <= 0.0) {
            return $selected;
        }

        $factor = $totalJudgmentPoints / $mappedTotal;

        return collect($selected)
            ->map(fn (?float $score): ?float => $score !== null ? round($score * $factor, 2) : null)
            ->all();
    }

    public function buildGroupKey(?string $sectionNumber, string $sectionTitle): string
    {
        $number = Str::of((string) $sectionNumber)
            ->trim()
            ->replaceMatches('/\s+/', ' ')
            ->toString();

        $title = Str::of($this->normalizeSectionTitle($sectionTitle))
            ->ascii()
            ->lower()
            ->replaceMatches('/[^a-z0-9]+/', '-')
            ->trim('-')
            ->toString();

        $prefix = $number !== '' ? $number.'-' : '';

        return trim($prefix.$title, '-');
    }

    public function normalizeSectionTitle(string $sectionTitle): string
    {
        return SectionTitleNormalizer::normalize($sectionTitle);
    }

    public function formatSubcriterionTitle(string $title): string
    {
        $normalized = trim(preg_replace('/\s+/u', ' ', $title) ?? $title);

        if ($normalized === '') {
            return '';
        }

        return mb_convert_case(mb_strtolower($normalized, 'UTF-8'), MB_CASE_TITLE, 'UTF-8');
    }

    private function parseNumericValue(mixed $value): ?float
    {
        if (is_int($value) || is_float($value)) {
            return (float) $value;
        }

        if (! is_string($value)) {
            return null;
        }

        $normalized = str_replace(',', '.', trim($value));

        if ($normalized === '') {
            return null;
        }

        if (preg_match('/-?\d+(?:\.\d+)?/', $normalized, $matches) !== 1) {
            return null;
        }

        return (float) $matches[0];
    }
}
