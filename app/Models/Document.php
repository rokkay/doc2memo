<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property int $tender_id
 * @property string $document_type
 * @property string $status
 * @property-read Tender $tender
 */
class Document extends Model
{
    use HasFactory;

    protected $fillable = [
        'tender_id',
        'document_type',
        'original_filename',
        'stored_filename',
        'file_path',
        'file_size',
        'mime_type',
        'status',
        'insights_count',
        'extracted_text',
        'processing_error',
        'analyzed_at',
    ];

    protected function casts(): array
    {
        return [
            'analyzed_at' => 'datetime',
            'insights_count' => 'integer',
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
     * @return HasMany<ExtractedCriterion, $this>
     */
    public function extractedCriteria(): HasMany
    {
        return $this->hasMany(ExtractedCriterion::class);
    }

    /**
     * @return HasMany<ExtractedSpecification, $this>
     */
    public function extractedSpecifications(): HasMany
    {
        return $this->hasMany(ExtractedSpecification::class);
    }

    /**
     * @return HasMany<DocumentInsight, $this>
     */
    public function insights(): HasMany
    {
        return $this->hasMany(DocumentInsight::class);
    }

    /**
     * @return HasMany<AiCostEntry, $this>
     */
    public function aiCostEntries(): HasMany
    {
        return $this->hasMany(AiCostEntry::class);
    }
}
