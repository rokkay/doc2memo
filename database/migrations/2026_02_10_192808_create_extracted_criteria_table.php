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
        Schema::create('extracted_criteria', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tender_id')->constrained()->onDelete('cascade');
            $table->foreignId('document_id')->constrained()->onDelete('cascade');
            $table->string('section_number')->nullable();
            $table->string('section_title');
            $table->text('description');
            $table->enum('priority', ['mandatory', 'preferable', 'optional'])->default('mandatory');
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['tender_id', 'section_number']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('extracted_criteria');
    }
};
