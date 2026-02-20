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
        Schema::create('lunch_settlements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained()->cascadeOnDelete();
            $table->foreignId('semester_id')->constrained()->cascadeOnDelete();
            $table->foreignId('staff_id')->constrained('school_staff')->cascadeOnDelete();
            $table->string('year_month'); // YYYY-MM
            $table->integer('days_count')->default(0);
            $table->integer('amount')->default(0);
            $table->string('status')->default('draft'); // draft, sent, paid
            $table->json('check_in_dates')->nullable(); // Snapshot of dates
            $table->integer('snapshot_price')->default(0); // Price at the time of settlement
            $table->timestamps();

            // Ensure unique settlement per staff per month
            $table->unique(['school_id', 'staff_id', 'year_month']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('lunch_settlements');
    }
};
