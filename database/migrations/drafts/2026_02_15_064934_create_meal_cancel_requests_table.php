<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// 建議引入 Enum
// 建議引入 Enum

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('meal_cancel_requests', function (Blueprint $table) {
            $table->id();

            // 1. 歸屬權
            $table->foreignId('school_id')->constrained()->cascadeOnDelete();
            $table->foreignId('semester_id')->constrained()->cascadeOnDelete();

            // 2. 對象 (可能是學生或教職)
            $table->foreignId('student_id')->nullable()->constrained()->cascadeOnDelete();
            $table->foreignId('school_staff_id')->nullable()->constrained('school_staff')->cascadeOnDelete();
            $table->foreignId('classroom_id')->nullable()->constrained()->nullOnDelete();

            // 3. 時間範圍 (停餐區間)
            $table->date('start_date')->index();
            $table->date('end_date')->index();

            // 4. [關鍵修正] 類型擴充
            // 除了病假(Sick)、事假(Personal)
            // 這裡要支援：'administrative' (行政停餐), 'arrears' (欠費停餐), 'quit' (中途退訂)
            $table->string('leave_type')->default('personal')->index();

            // 5. [宗師新增] 來源識別
            // 用來區分是「家長APP送的」還是「行政後台Key的」
            // 'parent_app', 'admin_panel', 'system_batch' (ex: 全校停課)
            $table->string('origin')->default('parent_app');

            // 6. 狀態機
            // 若 origin === 'admin_panel'，則 create 時直接設為 'approved'
            $table->string('status')->default('pending')->index();

            // 7. 審核/操作紀錄
            $table->foreignId('reviewed_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->dateTime('reviewed_at')->nullable();

            // 駁回或停餐原因 (手動停餐時，這裡填寫理由，ex: 嚴重違規、未繳費)
            $table->string('reject_reason')->nullable()->comment('駁回原因/停餐備註');

            // 8. 處理標記 (連動 DailyOrder)
            // 當這個 flag 變成 true，代表 Service 已經去把 daily_orders 的狀態改為 absence 了
            $table->boolean('is_processed')->default(false);

            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('meal_cancel_requests');
    }
};
