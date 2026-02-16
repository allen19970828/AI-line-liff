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
        Schema::table('semesters', function (Blueprint $table) {
            $table->integer('group_min_stop_days')->default(3)->after('min_stop_days')->comment('團體停餐最少天數');
            $table->boolean('auto_approve_mode')->default(false)->after('group_min_stop_days')->comment('是否開啟自動核准模式');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('semesters', function (Blueprint $table) {
            $table->dropColumn(['group_min_stop_days', 'auto_approve_mode']);
        });
    }
};
