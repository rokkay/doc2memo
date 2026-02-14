<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TechnicalMemory extends Model
{
    use HasFactory;

    protected $fillable = [
        'tender_id',
        'title',
        'status',
        'generated_file_path',
        'generated_at',
    ];

    protected function casts(): array
    {
        return [
            'generated_at' => 'datetime',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    public function tender(): BelongsTo
    {
        return $this->belongsTo(Tender::class);
    }

    public function sections(): HasMany
    {
        return $this->hasMany(TechnicalMemorySection::class)
            ->orderBy('sort_order')
            ->orderBy('id');
    }

    public function metricRuns(): HasMany
    {
        return $this->hasMany(TechnicalMemoryMetricRun::class)
            ->latest('id');
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
