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
        Schema::table('meal_cancel_requests', function (Blueprint $table) {
            $table->dropForeign(['student_id']);
            $table->dropIndex(['student_id', 'start_date', 'end_date']);
            $table->dropColumn('student_id');

            $table->morphs('cancellable'); // cancellable_id, cancellable_type

            // Re-add index for performance
            $table->index(['cancellable_type', 'cancellable_id', 'start_date', 'end_date'], 'idx_cancellable_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('meal_cancel_requests', function (Blueprint $table) {
            $table->dropIndex('idx_cancellable_date');
            $table->dropMorphs('cancellable');

            $table->foreignId('student_id')->constrained()->cascadeOnDelete();
            $table->index(['student_id', 'start_date', 'end_date']);
        });
    }
};
