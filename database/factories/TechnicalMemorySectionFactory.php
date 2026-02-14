<?php

namespace Database\Factories;

use App\Models\TechnicalMemory;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\TechnicalMemorySection>
 */
class TechnicalMemorySectionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'technical_memory_id' => TechnicalMemory::factory(),
            'group_key' => fake()->slug(3),
            'section_number' => fake()->optional()->regexify('[0-9]\.[0-9]'),
            'section_title' => fake()->sentence(4),
            'total_points' => fake()->randomFloat(2, 0, 100),
            'weight_percent' => fake()->randomFloat(2, 0, 100),
            'criteria_count' => fake()->numberBetween(1, 12),
            'sort_order' => fake()->numberBetween(1, 20),
            'status' => fake()->randomElement(['pending', 'generating', 'completed', 'failed']),
            'content' => fake()->optional()->paragraphs(3, true),
            'error_message' => null,
        ];
    }
}
