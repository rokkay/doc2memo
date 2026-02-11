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
        Schema::create('document_insights', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tender_id')->constrained()->cascadeOnDelete();
            $table->foreignId('document_id')->constrained()->cascadeOnDelete();
            $table->string('section_reference')->nullable();
            $table->string('topic');
            $table->enum('requirement_type', [
                'administrative',
                'technical',
                'budget',
                'timeline',
                'deliverable',
                'evaluation',
                'compliance',
                'risk',
            ]);
            $table->enum('importance', ['high', 'medium', 'low'])->default('medium');
            $table->text('statement');
            $table->text('evidence_excerpt')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['tender_id', 'document_id']);
            $table->index(['tender_id', 'importance']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('document_insights');
    }
};
