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
        Schema::table('extracted_criteria', function (Blueprint $table) {
            $table->string('source')
                ->default('analyzer')
                ->after('group_key');
            $table->decimal('confidence', 5, 2)
                ->nullable()
                ->after('source');

            $table->index(['document_id', 'source']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('extracted_criteria', function (Blueprint $table) {
            $table->dropIndex(['document_id', 'source']);
            $table->dropColumn(['source', 'confidence']);
        });
    }
};
