<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('technical_memory_generation_metrics', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('technical_memory_id')->constrained()->cascadeOnDelete();
            $table->foreignId('technical_memory_section_id')->constrained()->cascadeOnDelete();
            $table->uuid('run_id');
            $table->unsignedInteger('attempt');
            $table->string('status');
            $table->boolean('quality_passed');
            $table->json('quality_reasons')->nullable();
            $table->unsignedInteger('duration_ms');
            $table->unsignedInteger('output_chars');
            $table->string('model_name')->nullable();
            $table->decimal('estimated_input_units', total: 12, places: 4);
            $table->decimal('estimated_output_units', total: 12, places: 4);
            $table->decimal('estimated_cost_usd', total: 12, places: 6);
            $table->timestamps();

            $table->index(['technical_memory_id', 'created_at']);
            $table->index(['technical_memory_section_id', 'created_at']);
            $table->index(['status', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('technical_memory_generation_metrics');
    }
};
