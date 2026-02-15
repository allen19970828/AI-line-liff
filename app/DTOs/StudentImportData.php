<?php

namespace App\DTOs;

use App\Enums\DietaryType;
use App\Enums\SemesterStage;
use App\Enums\StudentIdentity;
use App\Enums\StudentStatus;

class StudentImportData
{
    public function __construct(
        public string $class_name,
        public string $seat_number,
        public string $student_code,
        public string $name,
        public SemesterStage $stage,       // [新增] 判斷學制
        public StudentIdentity $identity,
        public StudentStatus $status,
        public DietaryType $dietary_type,
    ) {}

    public static function fromRow(array $row): self
    {
        // [宗師金牌] 容錯處理：支援「姓名」與「姓名 (必填)」兩種格式
        $name = trim(
            $row['姓名']
            ?? $row['姓名 (必填)']
            ?? $row['name']
            ?? ''
        );

        $code = trim(
            $row['學號']
            ?? $row['學號 (必填)']
            ?? $row['student_code']
            ?? ''
        );

        if (empty($name) || empty($code)) {
            throw new \Exception('姓名與學號為必填欄位');
        }

        // 2. 班級與座號
        $className = trim(
            $row['班級']
            ?? $row['班級 (必填)']
            ?? $row['class']
            ?? ''
        );

        $seatNumber = trim(
            $row['座號']
            ?? $row['座號 (必填)']
            ?? $row['seat']
            ?? ''
        );

        // 3. 智慧判斷學制 (Stage)
        // [宗師金牌] 修正邏輯：
        // - 包含「年」字 → 國小 (ex: 一年甲班、六年仁班)
        // - 不含「年」但有「班」→ 幼兒園 (ex: 太陽班、月亮班)
        $stage = SemesterStage::Elementary; // 預設國小

        if (! str_contains($className, '年') && str_contains($className, '班')) {
            // 沒有「年」字但有「班」字 → 幼兒園
            $stage = SemesterStage::Kindergarten;
        }

        // 4. 身分轉換 (中文 -> Enum)
        $idStr = $row['身分'] ?? $row['身分 (選填)'] ?? '';
        $identity = match (true) {
            str_contains($idStr, '貧') || str_contains($idStr, '低收') => StudentIdentity::POVERTY,
            str_contains($idStr, '原') => StudentIdentity::INDIGENOUS,
            str_contains($idStr, '儲') => StudentIdentity::SAVINGS,
            str_contains($idStr, '捐') => StudentIdentity::DONATION,
            default => StudentIdentity::SELF_PAY, // 預設自費
        };

        // 5. 飲食轉換
        $dietStr = $row['飲食'] ?? $row['飲食 (選填)'] ?? '';
        $dietaryType = match (true) {
            str_contains($dietStr, '全素') => DietaryType::Vegan,
            str_contains($dietStr, '蛋奶') => DietaryType::OvoLactoVegetarian,
            str_contains($dietStr, '素') => DietaryType::Vegetarian,
            default => DietaryType::Meat,
        };

        // 6. 狀態轉換
        $statusStr = $row['狀態'] ?? $row['狀態 (選填)'] ?? '';
        $status = match (true) {
            str_contains($statusStr, '轉出') => StudentStatus::TransferredOut,
            str_contains($statusStr, '休學') => StudentStatus::Suspension,
            str_contains($statusStr, '畢') => StudentStatus::Graduated,
            default => StudentStatus::Active,
        };

        return new self(
            class_name: $className,
            seat_number: $seatNumber,
            student_code: $code,
            name: $name,
            stage: $stage,
            identity: $identity,
            status: $status,
            dietary_type: $dietaryType
        );
    }

    public function toArray(): array
    {
        return [
            // 注意：這裡不回傳 class_name，因為 Service 要去查 classroom_id
            'seat_number' => $this->seat_number,
            'student_code' => $this->student_code,
            'name' => $this->name,
            'stage' => $this->stage,
            'identity' => $this->identity,
            'status' => $this->status,
            'dietary_type' => $this->dietary_type,
            'is_meal_user' => true, // 匯入預設都吃
        ];
    }
}
