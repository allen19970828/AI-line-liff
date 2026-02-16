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
        Schema::create('meal_cancellation_group_student', function (Blueprint $table) {
            $table->id();
            $table->foreignId('group_id')
                ->constrained('meal_cancellation_groups')
                ->cascadeOnDelete();
            $table->foreignId('student_id')
                ->constrained('students')
                ->cascadeOnDelete();
            $table->timestamps();

            // Indexes
            $table->unique(['group_id', 'student_id'], 'unique_group_student');
            $table->index('student_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('meal_cancellation_group_student');
    }
};
