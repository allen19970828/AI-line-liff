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
        Schema::create('semesters', function (Blueprint $table) {
            $table->id();

            // [1. 核心識別與分流]
            $table->foreignId('school_id')
                ->constrained()
                ->cascadeOnDelete()
                ->comment('所屬學校ID');

            // 關鍵欄位：區分是「國小部」還是「附幼部」
            // 透過此欄位，同一學期代碼(114-1)可以存在兩筆資料(一筆國小、一筆附幼)
            $table->string('stage')
                ->default('elementary')
                ->comment('學制/部別: elementary(國小), kindergarten(附幼), junior(國中)');

            $table->string('code')
                ->comment('學期代碼 (ex: 114-1)');

            $table->string('name')
                ->comment('學期名稱 (ex: 114學年度上學期)');

            // [2. 時空結界]
            $table->date('start_date')->comment('學期開始日');
            $table->date('end_date')->comment('學期結束日');

            // [3. 業務規則 - 退訂餐邏輯]
            $table->unsignedInteger('min_stop_days')
                ->default(3)
                ->comment('退餐(請假)需提前天數');

            $table->unsignedInteger('staff_stop_days')
                ->default(5)
                ->comment('教職員退餐提前天數');

            $table->unsignedInteger('public_leave_days')
                ->default(10)
                ->comment('公假/團體退餐提前天數');

            // [4. 教職員扣款規則]
            $table->string('staff_meal_mode')
                ->default('daily')
                ->comment('教職員扣款模式: daily(按日扣款), monthly(月結)');

            // [5. 計價核心]
            // 若是國小部：通常指午餐費
            // 若是附幼部：通常指全日餐費(早+午+點)總額，細項拆分在 cost_breakdown
            $table->unsignedInteger('meal_price')
                ->default(0)
                ->comment('每餐收費基準(定價)');

            // [6. 成本結構與水果日 (JSONB 擴充)]
            // 國小範例: {"ingredients": 35, "fuel": 5, "personnel": 10}
            // 附幼範例: {"morning_snack": 15, "lunch": 30, "afternoon_snack": 10}
            $table->jsonb('cost_breakdown')
                ->nullable()
                ->comment('成本結構與費用細項 (JSONB)');

            // 範例: [2, 4] 代表週二、週四有水果
            $table->jsonb('fruit_days')
                ->nullable()
                ->comment('水果供應日 (0=週日, 1=週一... 6=週六)');

            // [7. 狀態控制]
            $table->boolean('is_active')
                ->default(false)
                ->comment('啟用狀態');

            $table->timestamps();
            $table->softDeletes();

            // [索引定義]
            // 宗師指點：這裡加入了 'stage'，實現了「一校兩制」
            // 意思就是：同一所學校(1) + 同一個學期(114-1) + 國小部(primary) -> 唯一
            //           同一所學校(1) + 同一個學期(114-1) + 附幼部(kindergarten) -> 唯一
            $table->unique(['school_id', 'code', 'stage'], 'unique_semester_per_stage');

            // 加速查詢：給定日期，快速撈出學期
            $table->index(['school_id', 'start_date', 'end_date']);
        });

        // [資料庫層級防禦]
        // 確保結束日期一定晚於開始日期 (PostgreSQL語法)
        DB::statement('ALTER TABLE semesters ADD CONSTRAINT check_semester_dates CHECK (end_date >= start_date)');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('semesters');
    }
};
