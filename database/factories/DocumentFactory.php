<?php

namespace Database\Factories;

use App\Models\Document;
use App\Models\Tender;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Document>
 */
class DocumentFactory extends Factory
{
    protected $model = Document::class;

    public function definition(): array
    {
        return [
            'tender_id' => Tender::factory(),
            'document_type' => fake()->randomElement(['pca', 'ppt']),
            'original_filename' => fake()->word().'.pdf',
            'stored_filename' => uniqid().'.pdf',
            'file_path' => 'documents/'.fake()->randomNumber().'/test.pdf',
            'file_size' => fake()->numberBetween(1000, 100000),
            'mime_type' => 'application/pdf',
            'status' => fake()->randomElement(['uploaded', 'processing', 'analyzed', 'failed']),
            'insights_count' => fake()->numberBetween(0, 20),
            'processing_error' => null,
        ];
    }
}
