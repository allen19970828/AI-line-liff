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
        Schema::table('meal_complaints', function (Blueprint $table) {
            $table->unsignedTinyInteger('rating')->nullable()->after('content')->comment('評分 (1-5)');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('meal_complaints', function (Blueprint $table) {
            $table->dropColumn('rating');
        });
    }
};
