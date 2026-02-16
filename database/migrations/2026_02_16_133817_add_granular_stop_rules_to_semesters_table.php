<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('semesters', function (Blueprint $table) {
            // 新增「事假提前天數」
            $table->unsignedInteger('personal_stop_days')
                ->default(3)
                ->after('public_leave_days')
                ->comment('事假提前天數');

            // 新增「病假提前天數」
            $table->unsignedInteger('sick_stop_days')
                ->default(1)
                ->after('personal_stop_days')
                ->comment('病假提前天數');

            // 新增「退餐截單時間」
            $table->time('stop_cut_off_time')
                ->default('16:00')
                ->after('sick_stop_days')
                ->comment('退餐截單時間 (下班後不受理)');
        });

        // 更新 min_stop_days 的註解（透過原生 SQL）
        DB::statement("COMMENT ON COLUMN semesters.min_stop_days IS '最少停餐天數 (連續請假達此天數才退費)'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('semesters', function (Blueprint $table) {
            $table->dropColumn(['personal_stop_days', 'sick_stop_days', 'stop_cut_off_time']);
        });

        // 還原 min_stop_days 的註解
        DB::statement("COMMENT ON COLUMN semesters.min_stop_days IS '退餐(請假)需提前天數'");
    }
};
