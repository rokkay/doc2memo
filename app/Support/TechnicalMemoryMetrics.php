<?php

declare(strict_types=1);

namespace App\Support;

final class TechnicalMemoryMetrics
{
    public const string EVENT_STARTED = 'started';

    public const string EVENT_QUALITY_FAILED = 'quality_failed';

    public const string EVENT_COMPLETED = 'completed';

    public const string EVENT_FAILED = 'failed';

    public const string EVENT_REQUEUED = 'requeued';
}
