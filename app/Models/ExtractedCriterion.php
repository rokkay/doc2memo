<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ExtractedCriterion extends Model
{
    use HasFactory;

    protected $table = 'extracted_criteria';

    protected $fillable = [
        'tender_id',
        'document_id',
        'section_number',
        'section_title',
        'description',
        'priority',
        'criterion_type',
        'score_points',
        'group_key',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'metadata' => 'array',
            'score_points' => 'decimal:2',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    public function scopeJudgment(Builder $query): Builder
    {
        return $query->where('criterion_type', 'judgment');
    }

    public function scopeAutomatic(Builder $query): Builder
    {
        return $query->where('criterion_type', 'automatic');
    }

    public function tender(): BelongsTo
    {
        return $this->belongsTo(Tender::class);
    }

    public function document(): BelongsTo
    {
        return $this->belongsTo(Document::class);
    }
}
