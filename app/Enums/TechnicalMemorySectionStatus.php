<?php

declare(strict_types=1);

namespace App\Enums;

enum TechnicalMemorySectionStatus: string
{
    case Pending = 'pending';
    case Generating = 'generating';
    case Completed = 'completed';
    case Failed = 'failed';

    /**
     * @return array<int,string>
     */
    public static function activeValues(): array
    {
        return [
            self::Pending->value,
            self::Generating->value,
        ];
    }

    /**
     * @return array<int,string>
     */
    public static function blockingValues(): array
    {
        return [
            self::Pending->value,
            self::Generating->value,
            self::Failed->value,
        ];
    }
}
