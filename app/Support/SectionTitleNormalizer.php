<?php

declare(strict_types=1);

namespace App\Support;

final class SectionTitleNormalizer
{
    public static function normalize(string $sectionTitle): string
    {
        $normalized = trim(preg_replace('/\s+/u', ' ', $sectionTitle) ?? $sectionTitle);

        $normalized = preg_replace(
            '/^criterios?\s+(?:de\s+)?adjudicaci[oó]n\s*\(?[a-z]\)?\s*juicio\s+de\s+valor\s*[-:–—]?\s*/iu',
            '',
            $normalized,
        ) ?? $normalized;

        return trim($normalized) !== '' ? trim($normalized) : 'Sin sección';
    }

    public static function heading(?string $sectionNumber, string $sectionTitle): string
    {
        $number = trim((string) $sectionNumber);
        $prefix = $number !== '' ? $number.' ' : '';

        return trim($prefix.self::normalize($sectionTitle));
    }
}
