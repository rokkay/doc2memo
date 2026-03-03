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
        'source',
        'confidence',
        'source_reference',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'metadata' => 'array',
            'score_points' => 'decimal:2',
            'confidence' => 'decimal:2',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    #[\Illuminate\Database\Eloquent\Attributes\Scope]
    protected function judgment(Builder $query): Builder
    {
        return $query->where('criterion_type', 'judgment');
    }

    #[\Illuminate\Database\Eloquent\Attributes\Scope]
    protected function automatic(Builder $query): Builder
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
