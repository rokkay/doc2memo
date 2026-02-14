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
        Schema::table('technical_memory_generation_metrics', function (Blueprint $table): void {
            $table->json('agent_cost_breakdown')->nullable()->after('estimated_cost_usd');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('technical_memory_generation_metrics', function (Blueprint $table): void {
            $table->dropColumn('agent_cost_breakdown');
        });
    }
};
