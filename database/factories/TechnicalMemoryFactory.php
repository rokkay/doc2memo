<?php

namespace Database\Factories;

use App\Models\TechnicalMemory;
use App\Models\Tender;
use Illuminate\Database\Eloquent\Factories\Factory;

class TechnicalMemoryFactory extends Factory
{
    protected $model = TechnicalMemory::class;

    public function definition(): array
    {
        return [
            'tender_id' => Tender::factory(),
            'title' => fake()->sentence(6),
            'introduction' => fake()->optional()->paragraph(),
            'company_presentation' => fake()->optional()->paragraph(),
            'technical_approach' => fake()->optional()->paragraph(),
            'methodology' => fake()->optional()->paragraph(),
            'team_structure' => fake()->optional()->paragraph(),
            'timeline' => fake()->optional()->paragraph(),
            'quality_assurance' => fake()->optional()->paragraph(),
            'risk_management' => fake()->optional()->paragraph(),
            'compliance_matrix' => fake()->optional()->paragraph(),
            'full_report_markdown' => fake()->optional()->paragraphs(3, true),
            'status' => fake()->randomElement(['draft', 'generated', 'reviewed', 'final']),
            'generated_file_path' => fake()->optional()->filePath(),
            'generated_at' => fake()->optional()->dateTime(),
        ];
    }
}
