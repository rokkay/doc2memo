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
            $table->dropColumn([
                'introduction',
                'company_presentation',
                'technical_approach',
                'methodology',
                'team_structure',
                'timeline',
                'timeline_plan',
                'quality_assurance',
                'risk_management',
                'compliance_matrix',
            ]);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('technical_memories', function (Blueprint $table) {
            $table->text('introduction')->nullable();
            $table->text('company_presentation')->nullable();
            $table->text('technical_approach')->nullable();
            $table->text('methodology')->nullable();
            $table->text('team_structure')->nullable();
            $table->text('timeline')->nullable();
            $table->json('timeline_plan')->nullable();
            $table->text('quality_assurance')->nullable();
            $table->text('risk_management')->nullable();
            $table->text('compliance_matrix')->nullable();
        });
    }
};
