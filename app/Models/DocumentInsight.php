<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DocumentInsight extends Model
{
    /** @use HasFactory<\Database\Factories\DocumentInsightFactory> */
    use HasFactory;

    protected $fillable = [
        'tender_id',
        'document_id',
        'section_reference',
        'topic',
        'requirement_type',
        'importance',
        'statement',
        'evidence_excerpt',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'metadata' => 'array',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
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
