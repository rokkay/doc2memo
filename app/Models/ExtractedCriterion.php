<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $tender_id
 * @property int|null $document_id
 * @property string|null $section_number
 * @property string|null $section_title
 * @property string|null $description
 * @property string $criterion_type
 * @property string|null $source
 * @property float|string|null $score_points
 * @property string|null $source_reference
 * @property array<string, mixed>|null $metadata
 */
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

    /**
     * @return BelongsTo<Tender, $this>
     */
    public function tender(): BelongsTo
    {
        return $this->belongsTo(Tender::class);
    }

    /**
     * @return BelongsTo<Document, $this>
     */
    public function document(): BelongsTo
    {
        return $this->belongsTo(Document::class);
    }
}
