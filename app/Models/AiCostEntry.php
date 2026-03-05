<?php

namespace App\Models;

use App\Enums\AiCostCategory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $tender_id
 * @property int|null $document_id
 * @property AiCostCategory $category
 */
class AiCostEntry extends Model
{
    use HasFactory;

    protected $fillable = [
        'tender_id',
        'document_id',
        'technical_memory_id',
        'technical_memory_section_id',
        'technical_memory_generation_metric_id',
        'run_id',
        'attempt',
        'category',
        'agent_key',
        'model_name',
        'status',
        'input_chars',
        'output_chars',
        'prompt_tokens',
        'completion_tokens',
        'cache_write_input_tokens',
        'cache_read_input_tokens',
        'reasoning_tokens',
        'estimated_input_units',
        'estimated_output_units',
        'estimated_cost_usd',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'attempt' => 'integer',
            'category' => AiCostCategory::class,
            'input_chars' => 'integer',
            'output_chars' => 'integer',
            'prompt_tokens' => 'integer',
            'completion_tokens' => 'integer',
            'cache_write_input_tokens' => 'integer',
            'cache_read_input_tokens' => 'integer',
            'reasoning_tokens' => 'integer',
            'estimated_input_units' => 'decimal:4',
            'estimated_output_units' => 'decimal:4',
            'estimated_cost_usd' => 'decimal:6',
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

    /**
     * @return BelongsTo<TechnicalMemory, $this>
     */
    public function technicalMemory(): BelongsTo
    {
        return $this->belongsTo(TechnicalMemory::class);
    }

    /**
     * @return BelongsTo<TechnicalMemorySection, $this>
     */
    public function technicalMemorySection(): BelongsTo
    {
        return $this->belongsTo(TechnicalMemorySection::class);
    }

    /**
     * @return BelongsTo<TechnicalMemoryGenerationMetric, $this>
     */
    public function technicalMemoryGenerationMetric(): BelongsTo
    {
        return $this->belongsTo(TechnicalMemoryGenerationMetric::class);
    }
}
