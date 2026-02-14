<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TechnicalMemoryMetricEvent extends Model
{
    use HasFactory;

    protected $fillable = [
        'technical_memory_id',
        'technical_memory_section_id',
        'run_id',
        'attempt',
        'event_type',
        'status',
        'duration_ms',
        'quality_passed',
        'quality_reasons',
        'output_chars',
        'output_h3_count',
        'used_style_editor',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'attempt' => 'integer',
            'duration_ms' => 'integer',
            'quality_passed' => 'boolean',
            'quality_reasons' => 'array',
            'output_chars' => 'integer',
            'output_h3_count' => 'integer',
            'used_style_editor' => 'boolean',
            'metadata' => 'array',
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
}
