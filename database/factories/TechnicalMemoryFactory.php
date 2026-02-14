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
            'status' => fake()->randomElement(['draft', 'generated', 'reviewed', 'final']),
            'generated_file_path' => fake()->optional()->filePath(),
            'generated_at' => fake()->optional()->dateTime(),
        ];
    }
}
