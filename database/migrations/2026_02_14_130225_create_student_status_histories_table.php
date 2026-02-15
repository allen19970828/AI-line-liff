<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// 引入 Enum 以獲取預設值或參考

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('student_status_histories', function (Blueprint $table) {
            $table->id();

            // 1. 多租戶核心
            $table->foreignId('school_id')->constrained()->cascadeOnDelete();

            // 2. 關聯學生
            $table->foreignId('student_id')->constrained()->cascadeOnDelete();

            // 3. 核心追蹤：身分、狀態與補助原因
            // [宗師修正] 改用 unsignedTinyInteger 配合 Int Enum
            $table->unsignedTinyInteger('identity')->index()->comment('身分別：對應 StudentIdentity Enum');

            // [新增] 狀態歷史：追蹤何時轉出、復學等軌跡
            $table->string('status')->index()->comment('就學狀態：對應 StudentStatus Enum');

            // [新增] 補助原因歷史：身分雖同為「貧困」，但原因代碼可能異動
            $table->string('subsidy_reason')->nullable()->index()->comment('補助原因：對應 SubsidyReason Enum');

            // 4. 時光區間
            $table->date('start_date')->index();
            $table->date('end_date')->nullable()->index(); // NULL = 目前有效

            // 5. 異動備註 (例如：收到區公所公文)
            $table->string('note')->nullable();

            // 6. 系統紀錄 (是誰改的？建議加上 created_by，這裡先用標準 timestamps)
            $table->timestamps();

            // 宗師級索引優化：
            // 查詢場景：找「某學生」在「某一天」的身分
            // 索引順序：student_id (先過濾人) -> start_date (再過濾時間)
            $table->index(['student_id', 'start_date', 'end_date'], 'idx_history_lookup');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('student_status_histories');
    }
};
