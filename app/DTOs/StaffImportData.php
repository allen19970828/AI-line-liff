<?php

namespace App\DTOs;

use App\Enums\DietaryType;
use App\Enums\SemesterStage;
use App\Enums\StaffRole;     // [新增] 引入身分 Enum
use Illuminate\Support\Arr;

class StaffImportData
{
    public function __construct(
        public ?string $lunch_code,
        public string $name,
        public SemesterStage $stage,
        public ?string $job_title,
        public StaffRole $staff_role,
        public DietaryType $dietary_type,
        public bool $is_meal_user,
        public ?array $meal_days = null,
    ) {}

    public static function fromRow(array $row): self
    {
        // 1. 姓名 (唯一嚴格檢查)
        $name = trim(Arr::get($row, '姓名') ?? Arr::get($row, 'name') ?? '');
        if (empty($name)) {
            throw new \Exception('Row data missing name: '.json_encode($row));
        }

        // 2. 處理部別 (SemesterStage)
        $stageStr = trim(Arr::get($row, '部別') ?? Arr::get($row, 'stage') ?? '');
        $stage = match (true) {
            str_contains($stageStr, '幼') => SemesterStage::Kindergarten,
            default => SemesterStage::Elementary, // 預設國小
        };

        // 3. 處理飲食 (DietaryType)
        $dietStr = trim(Arr::get($row, '飲食') ?? Arr::get($row, 'diet') ?? '');
        $dietaryType = match (true) {
            str_contains($dietStr, '全素') => DietaryType::Vegan,
            str_contains($dietStr, '蛋奶') => DietaryType::OvoLacto,
            str_contains($dietStr, '素') => DietaryType::Vegetarian, // 廣義素食
            default => DietaryType::Meat,
        };

        // 4. [升級] 處理身分 (StaffRole)
        // 不再看「導師資格」，單純看「身分」或「職稱」
        // 如果沒填，預設就是 Teacher (最廣義的教職員)
        $roleStr = trim(Arr::get($row, '身分') ?? Arr::get($row, 'role') ?? Arr::get($row, '職稱') ?? '');

        $staffRole = match (true) {
            str_contains($roleStr, '廚') || str_contains($roleStr, '工') => StaffRole::Worker,
            str_contains($roleStr, '職') || str_contains($roleStr, '行政') => StaffRole::Admin,
            default => StaffRole::Teacher, // 剩下的通通歸類為教師/一般人員
        };

        // 5. 處理用餐意願
        $mealUserRaw = trim(Arr::get($row, '用餐') ?? '');
        // 邏輯：除非明確寫「否」或「不吃」，否則預設要吃
        $isMealUser = ! in_array($mealUserRaw, ['否', 'N', 'n', '0', 'false', '不吃']);

        // 6. 用餐日 (兼職用)
        // 假設 Excel 填 "1,3,5"
        $daysRaw = Arr::get($row, '用餐日');
        $mealDays = null;
        if (! empty($daysRaw)) {
            // 過濾非數字字元，並轉成 array
            $mealDays = collect(explode(',', (string) $daysRaw))
                ->map(fn ($d) => (int) filter_var($d, FILTER_SANITIZE_NUMBER_INT))
                ->filter() // 移除 0 或空值
                ->values()
                ->toArray();

            // 如果是空的 (例如原本填錯)，就設回 null (代表全職)
            if (empty($mealDays)) {
                $mealDays = null;
            }
        }

        return new self(
            lunch_code: Arr::get($row, '卡號') ?? Arr::get($row, '編號'),
            name: $name,
            stage: $stage,
            job_title: Arr::get($row, '職稱'),
            staff_role: $staffRole,
            dietary_type: $dietaryType,
            is_meal_user: $isMealUser,
            meal_days: $mealDays
        );
    }

    /**
     * 轉成陣列 (給 Model create/update 用)
     */
    public function toArray(): array
    {
        return [
            'lunch_code' => $this->lunch_code,
            'name' => $this->name,
            'stage' => $this->stage, // Laravel 9+ 存入 Model 時會自動轉 Enum value
            'job_title' => $this->job_title,
            'staff_role' => $this->staff_role, // [重要] 存入 Enum
            'dietary_type' => $this->dietary_type,
            'is_meal_user' => $this->is_meal_user,
            'meal_days' => $this->meal_days, // Model cast 會自動轉 JSON
        ];
    }
}
