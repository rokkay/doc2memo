<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Tender>
 */
class TenderFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'title' => fake()->sentence(4),
            'issuing_company' => fake()->company(),
            'description' => fake()->paragraph(),
            'deadline_date' => fake()->randomElement([
                fake()->date('Y-m-d'),
                '15 dias naturales desde la publicacion',
                '20 dias habiles desde el envio de invitaciones',
            ]),
            'reference_number' => fake()->optional()->regexify('[A-Z]{2}-[0-9]{4}-[0-9]{6}'),
            'status' => fake()->randomElement(['pending', 'analyzing', 'completed', 'failed']),
        ];
    }

    public function pending(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'pending',
        ]);
    }

    public function analyzing(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'analyzing',
        ]);
    }

    public function completed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'completed',
        ]);
    }

    public function failed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'failed',
        ]);
    }
}
