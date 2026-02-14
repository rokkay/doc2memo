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
        Schema::create('technical_memory_metric_runs', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('technical_memory_id')->constrained()->cascadeOnDelete();
            $table->string('run_id');
            $table->string('trigger');
            $table->string('status');
            $table->unsignedInteger('sections_total')->default(0);
            $table->unsignedInteger('sections_completed')->default(0);
            $table->unsignedInteger('sections_failed')->default(0);
            $table->unsignedInteger('sections_retried')->default(0);
            $table->unsignedInteger('duration_ms')->nullable();
            $table->timestamps();

            $table->index('technical_memory_id');
            $table->index('run_id');
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('technical_memory_metric_runs');
    }
};
