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
        // 1. 問卷主檔
        Schema::create('surveys', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained()->cascadeOnDelete();
            $table->foreignId('semester_id')->nullable()->constrained()->nullOnDelete();

            $table->string('title');
            $table->text('description')->nullable();

            // ⭐ 修正 1: 報表歸屬月份移到這裡 (Campaign 模式)
            $table->date('period_month')->nullable()->index()->comment('歸屬月份 (ex: 2026-09-01)');

            // ⭐ 修正 2: 支援複選角色
            $table->json('target_roles')->nullable()->comment('["student", "staff", "parent"]');

            // ⭐ 優化 1: 生命週期管理 (取代單純的 is_active)
            $table->string('status')->default('draft')->index()->comment('draft, active, closed');

            $table->dateTime('start_at')->nullable();
            $table->dateTime('end_at')->nullable();

            // ⭐ 優化 2: 精準群眾定向 (年級與班級)
            $table->json('target_grade_levels')->nullable()->comment('指定年級 (null = 全校)');
            $table->json('target_class_ids')->nullable()->comment('指定班級 (null = 全校)');

            $table->timestamps();
            $table->softDeletes();
        });

        // 2. 題目檔
        Schema::create('survey_questions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('survey_id')->constrained()->cascadeOnDelete();

            $table->string('label'); // 題目顯示文字

            // ⭐ 修正 3: 分析代碼 (給 AI 或報表歸類用)
            $table->string('analysis_code')->nullable()->index()->comment('分析歸類碼 (ex: main_dish_taste)');

            $table->string('type')->default('rating_5');
            $table->json('options')->nullable(); // Select/Radio 的選項

            $table->integer('sort_order')->default(0);
            $table->boolean('is_required')->default(true);
            $table->timestamps();
            $table->softDeletes(); // ⭐ 優化 3: 保留題目刪除紀錄，避免歷史報表孤兒
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('survey_questions');
        Schema::dropIfExists('surveys');
    }
};
