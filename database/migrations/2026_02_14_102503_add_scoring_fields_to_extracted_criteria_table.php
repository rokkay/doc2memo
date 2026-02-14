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
            $table->enum('criterion_type', ['judgment', 'automatic'])
                ->default('judgment')
                ->after('priority');
            $table->decimal('score_points', 8, 2)
                ->nullable()
                ->after('criterion_type');
            $table->string('group_key')
                ->nullable()
                ->after('score_points');

            $table->index(['tender_id', 'group_key']);
            $table->index(['tender_id', 'criterion_type']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('extracted_criteria', function (Blueprint $table) {
            $table->dropIndex(['tender_id', 'group_key']);
            $table->dropIndex(['tender_id', 'criterion_type']);
            $table->dropColumn(['criterion_type', 'score_points', 'group_key']);
        });
    }
};
