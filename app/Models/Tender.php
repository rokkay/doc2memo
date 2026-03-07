<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * @property int $id
 * @property string $title
 * @property string $status
 * @property string|null $reference_number
 * @property-read \Illuminate\Database\Eloquent\Collection<int, Document> $documents
 * @property-read \Illuminate\Database\Eloquent\Collection<int, ExtractedCriterion> $extractedCriteria
 * @property-read \Illuminate\Database\Eloquent\Collection<int, ExtractedSpecification> $extractedSpecifications
 * @property-read \Illuminate\Database\Eloquent\Collection<int, DocumentInsight> $documentInsights
 * @property-read TechnicalMemory|null $technicalMemory
 * @property-read Document|null $pcaDocument
 * @property-read Document|null $pptDocument
 */
class Tender extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'issuing_company',
        'description',
        'deadline_date',
        'reference_number',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    /**
     * @return HasMany<Document, $this>
     */
    public function documents(): HasMany
    {
        return $this->hasMany(Document::class);
    }

    /**
     * @return HasMany<ExtractedCriterion, $this>
     */
    public function extractedCriteria(): HasMany
    {
        return $this->hasMany(ExtractedCriterion::class);
    }

    /**
     * @return HasMany<ExtractedCriterion, $this>
     */
    public function judgmentCriteria(): HasMany
    {
        return $this->hasMany(ExtractedCriterion::class)
            ->where('criterion_type', 'judgment');
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
    public function documentInsights(): HasMany
    {
        return $this->hasMany(DocumentInsight::class);
    }

    /**
     * @return HasOne<TechnicalMemory, $this>
     */
    public function technicalMemory(): HasOne
    {
        return $this->hasOne(TechnicalMemory::class);
    }

    /**
     * @return HasOne<Document, $this>
     */
    public function pcaDocument(): HasOne
    {
        return $this->hasOne(Document::class)->where('document_type', 'pca');
    }

    /**
     * @return HasOne<Document, $this>
     */
    public function pptDocument(): HasOne
    {
        return $this->hasOne(Document::class)->where('document_type', 'ppt');
    }
}
