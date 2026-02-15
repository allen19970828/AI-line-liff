<?php

use App\Enums\DietaryType;
use App\Enums\SemesterStage;
use App\Enums\StaffRole; // [修正] 已與現有 Enum 名稱對齊
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * * [宗師架構筆記]
     * SchoolStaff 表的核心職責是「人員主檔 (Identity)」。
     * 它記錄「這個人是誰」、「吃什麼」、「身分是什麼」。
     * 至於「他做什麼工作(導師/導護)」，則交由 PermanentDuty (常任職務表) 處理。
     */
    public function up(): void
    {
        Schema::create('school_staff', function (Blueprint $table) {
            $table->id();

            // --- 1. 歸屬權與帳號綁定 (Tenant & Auth) ---

            // 多租戶核心：所有資料必須掛在學校底下
            $table->foreignId('school_id')->constrained()->cascadeOnDelete();

            // 系統登入綁定：
            // nullable 是為了容許「不需要登入系統」的人員存在 (如：廚工、臨時人員)
            // nullOnDelete 確保若 User 帳號被刪，Staff 資料還在 (保留歷史帳務)
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();

            // --- 2. 識別資料 (Identity) ---

            // 硬體整合關鍵：
            // 用於刷卡機或條碼掃描，必須確保同校唯一。
            $table->string('lunch_code')->nullable()->comment('用餐人員編號/感應卡號');

            $table->string('name')->comment('姓名');

            // 顯示用職稱：
            // 僅供印名冊時識別 (ex: 校長, 幹事, 廚工)，不參與任何程式邏輯判斷。
            $table->string('job_title')->nullable()->comment('顯示用職稱');

            // --- 3. 業務邏輯核心 (Business Logic) ---

            // [身分分類 StaffRole]：
            // 取代原本的 is_tutor。
            // 用來區分：Teacher(教師-可當導師), Admin(職員), Worker(工友/廚工)。
            // 這是「資格」的過濾器。
            $table->string('staff_role')
                ->default(StaffRole::Teacher->value)
                ->index()
                ->comment('身分類別：教師、職員、職工');

            // [成本中心 SemesterStage]：
            // 決定這筆午餐費是算「國小部帳」還是「幼兒園帳」。
            // 這是會計邏輯的基礎。
            $table->string('stage')
                ->default(SemesterStage::Elementary->value)
                ->index();

            // --- 4. 用餐設定 (Meal Settings) ---

            // 飲食習慣：堅持用 String 存代碼，配合 Enum 使用，方便擴充 (蛋奶素、全素...)
            $table->string('dietary_type')->default(DietaryType::Meat->value);

            // 總開關：
            // 若為 false，系統自動忽略此人 (不產單、不計費)。
            // 適用於：長期請假、自備午餐者。
            $table->boolean('is_meal_user')->default(true);

            // 兼職救星 (Smart Schedule)：
            // 使用 PostgreSQL jsonb 儲存每週用餐日 (ex: [1, 3, 5])
            // 系統在產生訂單時，會檢查此欄位。若為 null 代表週一至週五都吃。
            $table->jsonb('meal_days')->nullable();

            // --- 5. 系統狀態 (System Status) ---

            // 在職狀態：離職後設為 false，保留資料但不顯示於選單。
            $table->boolean('is_active')->default(true);

            $table->timestamps();
            $table->softDeletes(); // 軟刪除：校務系統絕對不能真刪除資料

            // --- 6. 索引優化 (Indexes) ---

            // 防止同一學校有重複卡號 (硬體整合防呆)
            $table->unique(['school_id', 'lunch_code']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('school_staff');
    }
};
