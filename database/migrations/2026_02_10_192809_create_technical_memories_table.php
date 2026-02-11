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
        Schema::create('technical_memories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tender_id')->constrained()->onDelete('cascade');
            $table->string('title');
            $table->text('introduction')->nullable();
            $table->text('company_presentation')->nullable();
            $table->text('technical_approach')->nullable();
            $table->text('methodology')->nullable();
            $table->text('team_structure')->nullable();
            $table->text('timeline')->nullable();
            $table->text('quality_assurance')->nullable();
            $table->text('risk_management')->nullable();
            $table->text('compliance_matrix')->nullable();
            $table->enum('status', ['draft', 'generated', 'reviewed', 'final'])->default('draft');
            $table->string('generated_file_path')->nullable();
            $table->timestamp('generated_at')->nullable();
            $table->timestamps();

            $table->index('tender_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('technical_memories');
    }
};
