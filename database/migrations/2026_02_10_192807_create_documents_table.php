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
        Schema::create('documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tender_id')->constrained()->onDelete('cascade');
            $table->enum('document_type', ['pca', 'ppt']);
            $table->string('original_filename');
            $table->string('stored_filename');
            $table->string('file_path');
            $table->integer('file_size');
            $table->string('mime_type');
            $table->enum('status', ['uploaded', 'processing', 'analyzed', 'failed'])->default('uploaded');
            $table->text('extracted_text')->nullable();
            $table->timestamp('analyzed_at')->nullable();
            $table->timestamps();

            $table->index(['tender_id', 'document_type']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('documents');
    }
};
