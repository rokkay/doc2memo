<?php

namespace App\Models;

use App\Enums\TechnicalMemorySectionStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TechnicalMemorySection extends Model
{
    /** @use HasFactory<\Database\Factories\TechnicalMemorySectionFactory> */
    use HasFactory;

    protected $fillable = [
        'technical_memory_id',
        'group_key',
        'section_number',
        'section_title',
        'total_points',
        'weight_percent',
        'criteria_count',
        'sort_order',
        'status',
        'content',
        'error_message',
    ];

    protected function casts(): array
    {
        return [
            'total_points' => 'decimal:2',
            'weight_percent' => 'decimal:2',
            'status' => TechnicalMemorySectionStatus::class,
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    public function technicalMemory(): BelongsTo
    {
        return $this->belongsTo(TechnicalMemory::class);
    }

    public function metricEvents(): HasMany
    {
        return $this->hasMany(TechnicalMemoryMetricEvent::class)
            ->latest('id');
    }

    public function generationMetrics(): HasMany
    {
        return $this->hasMany(TechnicalMemoryGenerationMetric::class)
            ->latest('id');
    }
}
