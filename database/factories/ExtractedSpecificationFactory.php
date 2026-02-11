<?php

namespace Database\Factories;

use App\Models\Document;
use App\Models\ExtractedSpecification;
use App\Models\Tender;
use Illuminate\Database\Eloquent\Factories\Factory;

class ExtractedSpecificationFactory extends Factory
{
    protected $model = ExtractedSpecification::class;

    public function definition(): array
    {
        return [
            'tender_id' => Tender::factory(),
            'document_id' => Document::factory(),
            'section_number' => fake()->optional()->regexify('[0-9]\.[0-9]'),
            'section_title' => fake()->sentence(4),
            'technical_description' => fake()->paragraph(),
            'requirements' => fake()->optional()->paragraph(),
            'deliverables' => fake()->optional()->paragraph(),
            'metadata' => null,
        ];
    }
}
