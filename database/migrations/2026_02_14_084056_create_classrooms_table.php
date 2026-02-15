<?php

use App\Enums\SemesterStage;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema; // 記得確認你的 Enum 命名空間

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('classrooms', function (Blueprint $table) {
            $table->id();

            // 1. 學校與學制歸屬
            $table->foreignId('school_id')->constrained()->cascadeOnDelete();

            // [關鍵] 學制：決定這班的學生/老師的補助邏輯 (國小部/幼兒園)
            // 預設為 elementary，配合 SchoolStaff 的 stage 邏輯
            $table->string('stage')->default(SemesterStage::Elementary->value)->index();

            // 2. 班級識別
            $table->unsignedInteger('grade')->comment('年級');
            $table->string('class_name', 50)->comment('班級名稱 (甲, 1, 忠...)');
            // 排序權重：寫入時計算，讀取時直接 sort
            $table->unsignedInteger('sort_order')->default(50)->index();

            // 3. 導師歸屬 (單一窗口原則)
            // 這裡綁定 SchoolStaff (nullable)，代表該班的「主要負責人」
            // 雙導師的情況，這裡掛「頭」，另一位透過 teacher_meal_qty 計算
            $table->foreignId('school_staff_id')
                ->nullable()
                ->constrained('school_staff')
                ->nullOnDelete();

            // [關鍵補充 1] 導師用餐份數 (Billing 核心)
            // 解決雙導師、實習老師或導師不吃的情況
            $table->unsignedTinyInteger('teacher_meal_qty')->default(1)->comment('導師用餐份數');

            // [關鍵補充 2] 班級屬性與位置
            // class_type: general(普通), special(特教), sports(體育)
            $table->string('class_type')->default('general');
            // location: 配送位置 (ex: A棟3樓)，給送餐人員看
            $table->string('location')->nullable()->comment('配送位置');

            // 4. 廠商指定 (Override)
            // 若為 null，則依照年級或全校預設；若有值，則這班吃這家的
            $table->foreignId('caterer_id')->nullable()->constrained()->nullOnDelete();

            // 5. 狀態
            // 畢業或廢班設為 false -> 該班導師自動失去補助資格
            $table->boolean('is_active')->default(true);

            $table->timestamps();
            $table->softDeletes();

            // 複合索引：防止同校、同部別、同年級、同班名重複建立
            $table->unique(['school_id', 'stage', 'grade', 'class_name'], 'unique_classroom_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('classrooms');
    }
};
