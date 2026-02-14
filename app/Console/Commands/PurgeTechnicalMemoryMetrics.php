<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\TechnicalMemoryMetricEvent;
use App\Models\TechnicalMemoryMetricRun;
use Illuminate\Console\Command;

class PurgeTechnicalMemoryMetrics extends Command
{
    protected $signature = 'technical-memory:purge-metrics';

    protected $description = 'Purge technical memory metric events and runs older than retention policy';

    public function handle(): int
    {
        $retentionDays = (int) config('technical_memory.metrics.retention_days', 90);
        $cutoff = now()->subDays($retentionDays);

        $purgedEvents = TechnicalMemoryMetricEvent::query()
            ->where('created_at', '<', $cutoff)
            ->delete();

        $purgedRuns = TechnicalMemoryMetricRun::query()
            ->where('created_at', '<', $cutoff)
            ->delete();

        $this->line(sprintf(
            'Purged technical memory metrics older than %d days. Events: %d, Runs: %d.',
            $retentionDays,
            $purgedEvents,
            $purgedRuns,
        ));

        return self::SUCCESS;
    }
}
