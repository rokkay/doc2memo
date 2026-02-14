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
        Schema::create('technical_memory_sections', function (Blueprint $table) {
            $table->id();
            $table->foreignId('technical_memory_id')->constrained()->cascadeOnDelete();
            $table->string('group_key');
            $table->string('section_number')->nullable();
            $table->string('section_title');
            $table->decimal('total_points', 8, 2)->default(0);
            $table->decimal('weight_percent', 6, 2)->default(0);
            $table->unsignedInteger('criteria_count')->default(0);
            $table->unsignedInteger('sort_order')->default(0);
            $table->enum('status', ['pending', 'generating', 'completed', 'failed'])->default('pending');
            $table->longText('content')->nullable();
            $table->text('error_message')->nullable();
            $table->timestamps();

            $table->index(['technical_memory_id', 'sort_order']);
            $table->index(['technical_memory_id', 'status']);
            $table->index(['technical_memory_id', 'group_key']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('technical_memory_sections');
    }
};
