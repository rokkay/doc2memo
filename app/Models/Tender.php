<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

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

    public function documents(): HasMany
    {
        return $this->hasMany(Document::class);
    }

    public function extractedCriteria(): HasMany
    {
        return $this->hasMany(ExtractedCriterion::class);
    }

    public function judgmentCriteria(): HasMany
    {
        return $this->extractedCriteria()->judgment();
    }

    public function extractedSpecifications(): HasMany
    {
        return $this->hasMany(ExtractedSpecification::class);
    }

    public function documentInsights(): HasMany
    {
        return $this->hasMany(DocumentInsight::class);
    }

    public function technicalMemory(): HasOne
    {
        return $this->hasOne(TechnicalMemory::class);
    }

    public function pcaDocument(): HasOne
    {
        return $this->hasOne(Document::class)->where('document_type', 'pca');
    }

    public function pptDocument(): HasOne
    {
        return $this->hasOne(Document::class)->where('document_type', 'ppt');
    }
}
