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
        Schema::table('technical_memories', function (Blueprint $table) {
            $table->dropColumn('full_report_markdown');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('technical_memories', function (Blueprint $table) {
            $table->longText('full_report_markdown')->nullable()->after('compliance_matrix');
        });
    }
};
