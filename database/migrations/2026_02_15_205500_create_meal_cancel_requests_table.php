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
        Schema::create('meal_cancel_requests', function (Blueprint $table) {
            $table->id();

            // 申請人 (User or Staff/Student?)
            // 決定：關聯到 Student，因為停餐是針對「學生」或「教職員」個體
            // 但考量到 LIFF 是 User 操作，這裡記錄 Student ID 比較直接
            $table->foreignId('student_id')->constrained()->cascadeOnDelete();

            // 申請日期區間
            $table->date('start_date');
            $table->date('end_date');

            // 停餐原因
            $table->string('leave_type')->default('personal'); // Enum: CancelLeaveType
            $table->string('reason')->nullable();

            // 狀態與來源
            $table->string('status')->default('pending'); // Enum: CancelRequestStatus
            $table->string('origin')->default('liff');   // liff, admin, system

            // 審核資訊
            $table->foreignId('approved_by')->nullable()->constrained('users');
            $table->timestamp('approved_at')->nullable();

            $table->timestamps();

            // 索引優化：查詢某學生在某段時間的停餐
            $table->index(['student_id', 'start_date', 'end_date']);
            // 索引優化：查詢某日的所有停餐 (用於計算可用餐人數)
            $table->index(['start_date', 'end_date', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('meal_cancel_requests');
    }
};
