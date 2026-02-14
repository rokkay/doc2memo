<?php

declare(strict_types=1);

use App\Actions\TechnicalMemories\RegenerateSectionAction;
use App\Enums\TechnicalMemorySectionStatus;
use App\Jobs\GenerateTechnicalMemorySection;
use App\Models\ExtractedCriterion;
use App\Models\TechnicalMemory;
use App\Models\TechnicalMemorySection;
use App\Models\Tender;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;

uses(RefreshDatabase::class);

it('resets and requeues a single section regeneration', function (): void {
    Queue::fake();

    $tender = Tender::factory()->create();
    $memory = TechnicalMemory::factory()->create([
        'tender_id' => $tender->id,
        'status' => 'generated',
        'generated_at' => now(),
    ]);

    $section = TechnicalMemorySection::factory()->create([
        'technical_memory_id' => $memory->id,
        'group_key' => '1.1-metodologia',
        'section_number' => '1.1',
        'section_title' => 'Metodología',
        'status' => TechnicalMemorySectionStatus::Completed,
        'content' => 'Contenido previo',
    ]);

    ExtractedCriterion::factory()->create([
        'tender_id' => $tender->id,
        'section_number' => '1.1',
        'section_title' => 'Metodología',
        'group_key' => '1.1-metodologia',
        'criterion_type' => 'judgment',
        'priority' => 'mandatory',
        'score_points' => 16,
    ]);

    (new RegenerateSectionAction)($memory, $section);

    $section = $section->fresh();
    $memory = $memory->fresh();

    expect($section?->status)->toBe(TechnicalMemorySectionStatus::Pending);
    expect($section?->content)->toBeNull();
    expect($memory?->status)->toBe('draft');
    expect($memory?->generated_at)->toBeNull();

    Queue::assertPushed(GenerateTechnicalMemorySection::class, function (GenerateTechnicalMemorySection $job) use ($section): bool {
        return $job->technicalMemorySectionId === $section?->id;
    });
});
