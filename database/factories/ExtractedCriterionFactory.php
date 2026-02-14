<?php

namespace Database\Factories;

use App\Models\Document;
use App\Models\ExtractedCriterion;
use App\Models\Tender;
use Illuminate\Database\Eloquent\Factories\Factory;

class ExtractedCriterionFactory extends Factory
{
    protected $model = ExtractedCriterion::class;

    public function definition(): array
    {
        return [
            'tender_id' => Tender::factory(),
            'document_id' => Document::factory(),
            'section_number' => fake()->optional()->regexify('[0-9]\.[0-9]'),
            'section_title' => fake()->sentence(4),
            'description' => fake()->paragraph(),
            'priority' => fake()->randomElement(['mandatory', 'preferable', 'optional']),
            'criterion_type' => 'judgment',
            'score_points' => fake()->optional()->randomFloat(2, 1, 60),
            'group_key' => strtolower(fake()->slug(3)),
            'source' => fake()->randomElement(['analyzer', 'dedicated_extractor', 'parser']),
            'confidence' => fake()->optional()->randomFloat(2, 0.5, 0.99),
            'metadata' => null,
        ];
    }
}
