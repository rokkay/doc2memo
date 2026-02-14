<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TechnicalMemoryMetricRun extends Model
{
    use HasFactory;

    protected $fillable = [
        'technical_memory_id',
        'run_id',
        'trigger',
        'status',
        'sections_total',
        'sections_completed',
        'sections_failed',
        'sections_retried',
        'duration_ms',
    ];

    protected function casts(): array
    {
        return [
            'sections_total' => 'integer',
            'sections_completed' => 'integer',
            'sections_failed' => 'integer',
            'sections_retried' => 'integer',
            'duration_ms' => 'integer',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    public function technicalMemory(): BelongsTo
    {
        return $this->belongsTo(TechnicalMemory::class);
    }
}
