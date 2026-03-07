<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property int $tender_id
 * @property string $title
 * @property string $status
 * @property-read Tender $tender
 * @property-read \Illuminate\Database\Eloquent\Collection<int, TechnicalMemorySection> $sections
 */
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

    /**
     * @return BelongsTo<Tender, $this>
     */
    public function tender(): BelongsTo
    {
        return $this->belongsTo(Tender::class);
    }

    /**
     * @return HasMany<TechnicalMemorySection, $this>
     */
    public function sections(): HasMany
    {
        return $this->hasMany(TechnicalMemorySection::class)
            ->orderBy('sort_order')
            ->orderBy('id');
    }

    /**
     * @return HasMany<TechnicalMemoryMetricRun, $this>
     */
    public function metricRuns(): HasMany
    {
        return $this->hasMany(TechnicalMemoryMetricRun::class)
            ->latest('id');
    }

    /**
     * @return HasMany<TechnicalMemoryMetricEvent, $this>
     */
    public function metricEvents(): HasMany
    {
        return $this->hasMany(TechnicalMemoryMetricEvent::class)
            ->latest('id');
    }

    /**
     * @return HasMany<TechnicalMemoryGenerationMetric, $this>
     */
    public function generationMetrics(): HasMany
    {
        return $this->hasMany(TechnicalMemoryGenerationMetric::class)
            ->latest('id');
    }

    /**
     * @return HasMany<AiCostEntry, $this>
     */
    public function aiCostEntries(): HasMany
    {
        return $this->hasMany(AiCostEntry::class);
    }
}
