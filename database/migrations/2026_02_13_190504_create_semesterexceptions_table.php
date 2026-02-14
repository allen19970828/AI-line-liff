<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 宗師建議：表名用 semester_exceptions 語意更精確 (只存例外)
        // 但若你喜歡 school_calendars 也可以，這裡我用 exceptions 示範
        Schema::create('semester_exceptions', function (Blueprint $table) {
            $table->id();

            // [1. 歸屬權 - 綁定學期]
            // 宗師指點：不需要 school_id，因為 semester_id 已經隱含了學校
            // 這樣做可以確保「國小」跟「附幼」的行事曆是獨立的！
            $table->foreignId('semester_id')->constrained()->cascadeOnDelete();

            // [2. 核心日期]
            $table->date('date');

            // [3. 事件類型 (這是從 NationalHoliday 繼承來的)]
            // 0: holiday (放假)
            // 1: makeup (補課 - 需供餐)
            // 2: activity (學校活動 - 不供餐)
            // 3: typhoon (天災 - 強制停餐)
            // 4: other (其他調整)
            $table->unsignedTinyInteger('type')
                ->default(0)
                ->comment('類型: 0=放假, 1=補課, 2=活動(不供餐), 3=天災');

            // [4. 供餐開關 (直觀控制)]
            // 雖然 type 可以判斷，但多這個欄位可以讓 SQL 查詢更簡單
            // 例如：SELECT * FROM exceptions WHERE is_meal_day = true
            $table->boolean('is_meal_day')
                ->default(false)
                ->comment('當日是否供餐 (快取欄位)');

            // [5. 適用年級 (你的神技)]
            // null = 全校適用 (Default)
            // JSON = ["6"] 代表只有六年級適用 (例如：六年級畢業旅行，其他年級照常上課/供餐)
            // 宗師提醒：這欄位超強，解決了「部分年級停餐」的痛點
            $table->jsonb('target_grades')
                ->nullable()
                ->comment('適用年級 (null=全該學制所有年級)');

            // [6. 說明與備註]
            $table->string('description')->nullable()->comment('ex: 校慶補假');

            // 是否讓家長看到？ (ex: 廚房歲修不供餐，家長需要知道原因)
            $table->boolean('show_in_reports')->default(true);

            $table->timestamps();

            // [7. 索引修正 - 關鍵！]
            // 確保同一個學期內，同一天只能有一個例外設定。
            // 這樣「國小部(114-1)」跟「附幼部(114-1)」在同一天可以有不同設定！
            $table->unique(['semester_id', 'date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('semester_exceptions');
    }
};
