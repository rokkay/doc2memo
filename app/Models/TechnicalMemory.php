<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TechnicalMemory extends Model
{
    use HasFactory;

    protected $fillable = [
        'tender_id',
        'title',
        'introduction',
        'company_presentation',
        'technical_approach',
        'methodology',
        'team_structure',
        'timeline',
        'timeline_plan',
        'quality_assurance',
        'risk_management',
        'compliance_matrix',
        'full_report_markdown',
        'status',
        'generated_file_path',
        'generated_at',
    ];

    protected function casts(): array
    {
        return [
            'generated_at' => 'datetime',
            'timeline_plan' => 'array',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    public function tender(): BelongsTo
    {
        return $this->belongsTo(Tender::class);
    }
}
