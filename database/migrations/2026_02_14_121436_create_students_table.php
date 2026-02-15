<?php

use App\Enums\DietaryType;
use App\Enums\SemesterStage;
use App\Enums\StudentIdentity;
use App\Enums\StudentStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('students', function (Blueprint $table) {
            $table->id();

            // 1. 學校與班級歸屬
            $table->foreignId('school_id')->constrained()->cascadeOnDelete();

            // 班級：允許 NULL (轉入未分班、畢業生、暫時安置)
            $table->foreignId('classroom_id')
                ->nullable()
                ->constrained('classrooms')
                ->nullOnDelete();

            // [一致性修正] 欄位名稱改為 stage，配合 Staff/Classroom
            $table->string('stage')->default(SemesterStage::Elementary->value)->index();

            // 2. 識別資料
            $table->string('name', 64)->index();
            $table->string('student_code', 32)->nullable()->index()->comment('學號');
            $table->string('seat_number', 10)->nullable()->comment('座號');

            // [新增] 身份證字號 (通常申請補助會用到，建議加密或隱碼，這裡先留白)
            // $table->string('nid_hash')->nullable();

            // 3. 生死簿 (使用 Enum)
            $table->string('status')->default(StudentStatus::Active->value)->index();

            // 4. 用餐設定 (Core Business)
            // [一致性修正] 改為 is_meal_user，與 Staff 統一
            $table->boolean('is_meal_user')->default(true)->comment('是否參加午餐');

            // 飲食習慣 (使用 Enum)
            $table->string('dietary_type')->default(DietaryType::Meat->value);

            // 幼兒園專屬設定 (點心)
            // 這些欄位如果 stage == elementary 則無效
            $table->boolean('eat_morning_snack')->default(false)->comment('幼:早點');
            $table->boolean('eat_lunch')->default(true)->comment('幼:午餐');
            $table->boolean('eat_afternoon_snack')->default(false)->comment('幼:午點');

            // 特殊用餐週期 (例如：資源班學生每週二去本校上課，不在分校吃)
            $table->jsonb('meal_days')->nullable();

            // 5. 身份與補助 (Finance)
            // [一致性修正] 改為 identity，明確表示身分別
            // 使用 unsignedTinyInteger 匹配 Int Backed Enum (StudentIdentity)
            $table->unsignedTinyInteger('identity')
                ->default(StudentIdentity::SELF_PAY->value)
                ->index()
                ->comment('身分別：對應 StudentIdentity Enum');

            // [宗師建議] 補助原因與 identity 風格大一統
            $table->string('subsidy_reason')
                ->nullable()
                ->index()
                ->comment('補助原因：對應 SubsidyReason Enum');

            // 證明文件：建議只存路徑，或是關聯到 media table
            $table->string('subsidy_document')->nullable();

            // 6. 退費帳戶 (資訊長架構)
            $table->string('payment_method')->default('bank'); // bank, cash
            // $table->string('bank_code', 3)->nullable();
            // $table->string('bank_account', 20)->nullable();
            // $table->string('account_holder')->nullable(); // 戶名 (通常是家長)

            // 7. 歷程紀錄
            $table->date('joined_at')->nullable()->comment('入學/轉入日');
            $table->date('left_at')->nullable()->comment('畢業/轉出日');
            $table->text('note')->nullable(); // 過敏源、家長交代事項

            $table->timestamps();
            $table->softDeletes();

            // 複合索引：同校學號唯一 (防止 Excel 匯入重複)
            $table->unique(['school_id', 'student_code']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('students');
    }
};
