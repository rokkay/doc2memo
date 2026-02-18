<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

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

    public function technicalMemory(): BelongsTo
    {
        return $this->belongsTo(TechnicalMemory::class);
    }

    public function technicalMemorySection(): BelongsTo
    {
        return $this->belongsTo(TechnicalMemorySection::class);
    }

    public function aiCostEntries(): HasMany
    {
        return $this->hasMany(AiCostEntry::class);
    }
}
