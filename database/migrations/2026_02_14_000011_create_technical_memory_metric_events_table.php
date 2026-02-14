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
        Schema::create('technical_memory_metric_events', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('technical_memory_id')->constrained()->cascadeOnDelete();
            $table->foreignId('technical_memory_section_id')->nullable()->constrained()->cascadeOnDelete();
            $table->string('run_id');
            $table->unsignedInteger('attempt');
            $table->string('event_type');
            $table->string('status')->nullable();
            $table->unsignedInteger('duration_ms')->nullable();
            $table->boolean('quality_passed')->nullable();
            $table->json('quality_reasons')->nullable();
            $table->unsignedInteger('output_chars')->nullable();
            $table->unsignedInteger('output_h3_count')->nullable();
            $table->boolean('used_style_editor')->default(true);
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index('technical_memory_id');
            $table->index('technical_memory_section_id');
            $table->index('run_id');
            $table->index('event_type');
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('technical_memory_metric_events');
    }
};
