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
        Schema::create('ai_cost_entries', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tender_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('document_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('technical_memory_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('technical_memory_section_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('technical_memory_generation_metric_id')->nullable()->constrained()->nullOnDelete();
            $table->string('run_id')->nullable();
            $table->unsignedInteger('attempt')->nullable();
            $table->string('category');
            $table->string('agent_key');
            $table->string('model_name')->nullable();
            $table->string('status');
            $table->unsignedInteger('input_chars')->default(0);
            $table->unsignedInteger('output_chars')->default(0);
            $table->unsignedInteger('prompt_tokens')->default(0);
            $table->unsignedInteger('completion_tokens')->default(0);
            $table->unsignedInteger('cache_write_input_tokens')->default(0);
            $table->unsignedInteger('cache_read_input_tokens')->default(0);
            $table->unsignedInteger('reasoning_tokens')->default(0);
            $table->decimal('estimated_input_units', total: 12, places: 4)->default(0);
            $table->decimal('estimated_output_units', total: 12, places: 4)->default(0);
            $table->decimal('estimated_cost_usd', total: 12, places: 6)->default(0);
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index('category');
            $table->index('agent_key');
            $table->index('run_id');
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ai_cost_entries');
    }
};
