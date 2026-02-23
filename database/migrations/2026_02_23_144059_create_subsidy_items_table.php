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
        Schema::create('subsidy_items', function (Blueprint $table) {
            $table->id();

            // 基礎關聯
            $table->foreignId('school_id')->constrained()->cascadeOnDelete();
            $table->foreignId('semester_id')->constrained()->cascadeOnDelete();

            // 1. 識別資訊
            $table->string('name')->comment('補助名稱 (ex: 教育部貧困)');
            $table->string('code')->nullable()->index()->comment('官方代碼');
            $table->text('description')->nullable()->comment('備註說明');

            // 2. 資格鎖定 (關鍵升級！支援多重身分)
            // target_roles: ['student', 'staff']
            // identity_tags: ['indigenous', 'homeroom', 'low_income']
            $table->json('target_roles')->nullable()->comment('適用對象: [student, staff]');
            $table->json('identity_tags')->nullable()->comment('身分標籤');

            // 3. 金額邏輯
            $table->string('source_type')->default('local')->index()->comment('來源類型: local, gov, donation');
            $table->boolean('is_full_coverage')->default(false)->comment('是否全額補助');
            $table->unsignedInteger('amount_per_meal')->default(0)->comment('單餐補助金額');
            $table->unsignedInteger('budget_limit')->default(0)->comment('預算上限 (0=無上限)');

            // 4. 優先權與狀態
            $table->unsignedTinyInteger('priority')->default(10)->comment('扣款優先序');
            $table->boolean('is_active')->default(true);

            $table->timestamps();

            // 複合索引
            $table->unique(['school_id', 'semester_id', 'code'], 'subsidy_items_unique_code');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('subsidy_items');
    }
};
