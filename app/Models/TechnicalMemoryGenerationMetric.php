<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property int $technical_memory_id
 * @property int|null $technical_memory_section_id
 * @property int $attempt
 * @property string $status
 * @property int|null $duration_ms
 * @property \Carbon\CarbonInterface|null $created_at
 */
class TechnicalMemoryGenerationMetric extends Model
{
    use HasFactory;

    protected $fillable = [
        'technical_memory_id',
        'technical_memory_section_id',
        'run_id',
        'attempt',
        'status',
        'quality_passed',
        'quality_reasons',
        'duration_ms',
        'output_chars',
        'model_name',
    ];

    protected function casts(): array
    {
        return [
            'attempt' => 'integer',
            'quality_passed' => 'boolean',
            'quality_reasons' => 'array',
            'duration_ms' => 'integer',
            'output_chars' => 'integer',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<TechnicalMemory, $this>
     */
    public function technicalMemory(): BelongsTo
    {
        return $this->belongsTo(TechnicalMemory::class);
    }

    /**
     * @return BelongsTo<TechnicalMemorySection, $this>
     */
    public function technicalMemorySection(): BelongsTo
    {
        return $this->belongsTo(TechnicalMemorySection::class);
    }

    /**
     * @return HasMany<AiCostEntry, $this>
     */
    public function aiCostEntries(): HasMany
    {
        return $this->hasMany(AiCostEntry::class);
    }
}
