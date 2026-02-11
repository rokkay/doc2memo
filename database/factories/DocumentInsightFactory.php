<?php

namespace Database\Factories;

use App\Models\Document;
use App\Models\Tender;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\DocumentInsight>
 */
class DocumentInsightFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'tender_id' => Tender::factory(),
            'document_id' => Document::factory(),
            'section_reference' => fake()->optional()->regexify('[A-Z]\.[0-9]'),
            'topic' => fake()->words(2, true),
            'requirement_type' => fake()->randomElement([
                'administrative',
                'technical',
                'budget',
                'timeline',
                'deliverable',
                'evaluation',
                'compliance',
                'risk',
            ]),
            'importance' => fake()->randomElement(['high', 'medium', 'low']),
            'statement' => fake()->sentence(14),
            'evidence_excerpt' => fake()->optional()->sentence(20),
            'metadata' => null,
        ];
    }
}
