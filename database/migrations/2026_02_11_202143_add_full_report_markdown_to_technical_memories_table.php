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
        Schema::table('technical_memories', function (Blueprint $table): void {
            $table->longText('full_report_markdown')->nullable()->after('compliance_matrix');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('technical_memories', function (Blueprint $table): void {
            $table->dropColumn('full_report_markdown');
        });
    }
};
