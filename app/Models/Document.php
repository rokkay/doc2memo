<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

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
        'estimated_analysis_input_units',
        'estimated_analysis_output_units',
        'estimated_analysis_cost_usd',
        'analysis_cost_breakdown',
        'extracted_text',
        'processing_error',
        'analyzed_at',
    ];

    protected function casts(): array
    {
        return [
            'analyzed_at' => 'datetime',
            'insights_count' => 'integer',
            'estimated_analysis_input_units' => 'decimal:4',
            'estimated_analysis_output_units' => 'decimal:4',
            'estimated_analysis_cost_usd' => 'decimal:6',
            'analysis_cost_breakdown' => 'array',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    public function tender(): BelongsTo
    {
        return $this->belongsTo(Tender::class);
    }

    public function extractedCriteria(): HasMany
    {
        return $this->hasMany(ExtractedCriterion::class);
    }

    public function extractedSpecifications(): HasMany
    {
        return $this->hasMany(ExtractedSpecification::class);
    }

    public function insights(): HasMany
    {
        return $this->hasMany(DocumentInsight::class);
    }
}
