<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $tender_id
 * @property int $document_id
 */
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
