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
        Schema::table('documents', function (Blueprint $table): void {
            $table->decimal('estimated_analysis_input_units', total: 12, places: 4)->default(0)->after('insights_count');
            $table->decimal('estimated_analysis_output_units', total: 12, places: 4)->default(0)->after('estimated_analysis_input_units');
            $table->decimal('estimated_analysis_cost_usd', total: 12, places: 6)->default(0)->after('estimated_analysis_output_units');
            $table->json('analysis_cost_breakdown')->nullable()->after('estimated_analysis_cost_usd');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('documents', function (Blueprint $table): void {
            $table->dropColumn([
                'estimated_analysis_input_units',
                'estimated_analysis_output_units',
                'estimated_analysis_cost_usd',
                'analysis_cost_breakdown',
            ]);
        });
    }
};
