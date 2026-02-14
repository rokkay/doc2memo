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
            $table->string('source_reference')
                ->nullable()
                ->after('confidence');

            $table->index(['document_id', 'source_reference']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('extracted_criteria', function (Blueprint $table) {
            $table->dropIndex(['document_id', 'source_reference']);
            $table->dropColumn('source_reference');
        });
    }
};
