<?php

declare(strict_types=1);

namespace App\Support;

final class TechnicalMemoryMetrics
{
    public const EVENT_STARTED = 'started';

    public const EVENT_QUALITY_FAILED = 'quality_failed';

    public const EVENT_COMPLETED = 'completed';

    public const EVENT_FAILED = 'failed';

    public const EVENT_REQUEUED = 'requeued';
}
