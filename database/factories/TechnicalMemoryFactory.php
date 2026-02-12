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
            'timeline_plan' => fake()->optional()->randomElement([
                [
                    'total_weeks' => 8,
                    'tasks' => [
                        [
                            'id' => 'task-analysis',
                            'title' => 'Analisis inicial',
                            'lane' => 'Planificacion',
                            'start_week' => 1,
                            'end_week' => 2,
                            'depends_on' => [],
                        ],
                        [
                            'id' => 'task-proposal',
                            'title' => 'Diseno y redaccion',
                            'lane' => 'Ejecucion',
                            'start_week' => 3,
                            'end_week' => 6,
                            'depends_on' => ['task-analysis'],
                        ],
                    ],
                    'milestones' => [
                        [
                            'title' => 'Entrega final',
                            'week' => 8,
                        ],
                    ],
                ],
                null,
            ]),
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
