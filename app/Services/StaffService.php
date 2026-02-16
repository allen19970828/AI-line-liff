<?php

namespace App\Services;

use App\DTOs\StaffImportData;
use App\Models\Staff; // [修正] 使用正確的 Model 名稱
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class StaffService
{
    /**
     * 處理匯入資料的「洗髓與定型」
     * 接收原始陣列 -> 轉 DTO -> 執行 Upsert
     */
    public function upsertStaffFromImport(array $row, int $schoolId): ?Staff
    {
        try {
            // 1. DTO 轉換 (這裡會檢查必填欄位)
            $dto = StaffImportData::fromRow($row);

            // 2. 執行核心邏輯
            return $this->upsertStaff($dto, $schoolId);

        } catch (\Exception $e) {
            Log::error("匯入教職員失敗: {$e->getMessage()}", ['row' => $row, 'school_id' => $schoolId]);

            return null; // 回傳 null 代表這行失敗
        }
    }

    /**
     * 核心邏輯：智能更新或新增
     */
    public function upsertStaff(StaffImportData $data, int $schoolId): Staff
    {
        return DB::transaction(function () use ($data, $schoolId) {

            // --- 步驟 A: 嘗試尋找現有員工 ---
            $existingStaff = null;

            // 1. 優先用卡號找 (最強證據)
            if (! empty($data->lunch_code)) {
                $existingStaff = Staff::where('school_id', $schoolId)
                    ->where('lunch_code', $data->lunch_code)
                    ->first();
            }

            // 2. 沒卡號，用「姓名 + 部別」找 (次強證據)
            if (! $existingStaff) {
                $existingStaff = Staff::where('school_id', $schoolId)
                    ->where('name', $data->name)
                    ->where('stage', $data->stage) // Enum 直接比對
                    ->first();
            }

            // --- 步驟 B: 決定最終寫入的卡號 ---
            if ($existingStaff) {
                // 如果是更新，且 Excel 有給新卡號，是否要更新卡號？
                // 這裡策略：保留原有卡號，除非 Excel 有給且不同 (視業務需求而定)
                // 宗師建議：卡號通常不動，除非是從無到有
                $finalCode = $existingStaff->lunch_code ?? $data->lunch_code;
            } else {
                // 如果是新增
                $finalCode = $data->lunch_code;
            }

            // 如果最後還是沒卡號，生成一個臨時的 (因為 lunch_code 在 DB 是 nullable，其實也可以留空)
            // 這裡保留你的生成邏輯，但建議非必要不生成，除非是為了刷卡機
            if (empty($finalCode)) {
                $finalCode = $this->generateSecureLunchCode($schoolId);
            }

            // --- 步驟 C: 寫入資料庫 ---
            // 使用 updateOrCreate，條件是 ID (如果找到) 或 卡號

            $matchAttributes = [];
            if ($existingStaff) {
                $matchAttributes = ['id' => $existingStaff->id];
            } elseif (! empty($finalCode)) {
                $matchAttributes = ['school_id' => $schoolId, 'lunch_code' => $finalCode];
            } else {
                // 極端情況：沒 ID 也沒卡號 -> 用姓名 + 部別當 key
                $matchAttributes = ['school_id' => $schoolId, 'name' => $data->name, 'stage' => $data->stage];
            }

            return Staff::updateOrCreate(
                $matchAttributes,
                [
                    'school_id' => $schoolId, // 確保 school_id 寫入 (如果是 create)
                    'lunch_code' => $finalCode,
                    'name' => $data->name,
                    'stage' => $data->stage,        // Enum
                    'job_title' => $data->job_title, // snake_case
                    'staff_role' => $data->staff_role, // [重要] 直接用 DTO 判斷好的 Enum
                    'dietary_type' => $data->dietary_type, // Enum
                    'is_meal_user' => $data->is_meal_user,
                    'meal_days' => $data->meal_days, // Array -> JSON
                    'is_active' => true,
                ]
            );
        });
    }

    /**
     * 生成安全的編號 (備用方案)
     */
    protected function generateSecureLunchCode(int $schoolId): string
    {
        // 宗師優化：使用正則表達式提取數字，相容性更高
        $lastCode = Staff::where('school_id', $schoolId)
            ->where('lunch_code', 'LIKE', 'lunch%')
            ->orderByRaw('LENGTH(lunch_code) DESC') // 先比長度
            ->orderBy('lunch_code', 'DESC')         // 再比字串
            ->value('lunch_code');

        $nextNumber = 1;

        if ($lastCode && preg_match('/lunch(\d+)/', $lastCode, $matches)) {
            $nextNumber = (int) $matches[1] + 1;
        }

        return 'lunch'.str_pad((string) $nextNumber, 3, '0', STR_PAD_LEFT);
    }
}
