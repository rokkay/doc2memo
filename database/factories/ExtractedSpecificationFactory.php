<?php

namespace Database\Factories;

use App\Models\Document;
use App\Models\ExtractedSpecification;
use App\Models\Tender;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\ExtractedSpecification>
 */
class ExtractedSpecificationFactory extends Factory
{
    protected $model = ExtractedSpecification::class;

    public function definition(): array
    {
        return [
            'tender_id' => Tender::factory(),
            'document_id' => Document::factory(),
            'section_number' => fake()->optional()->regexify('\d\.\d'),
            'section_title' => fake()->sentence(4),
            'technical_description' => fake()->paragraph(),
            'requirements' => fake()->optional()->paragraph(),
            'deliverables' => fake()->optional()->paragraph(),
            'metadata' => null,
        ];
    }
}
