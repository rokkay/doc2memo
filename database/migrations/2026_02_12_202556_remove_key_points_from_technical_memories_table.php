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
        if (! Schema::hasColumn('technical_memories', 'key_points')) {
            return;
        }

        Schema::table('technical_memories', function (Blueprint $table): void {
            $table->dropColumn('key_points');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasColumn('technical_memories', 'key_points')) {
            return;
        }

        Schema::table('technical_memories', function (Blueprint $table): void {
            $table->json('key_points')->nullable()->after('full_report_markdown');
        });
    }
};
