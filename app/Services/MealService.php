<?php

namespace App\Services;

use App\Enums\Meals\CancelRequestStatus;
use App\Models\MealCancelRequest;
use App\Models\User;
use Carbon\Carbon;

class MealService
{
    /**
     * 批量建立停餐申請 (支援多型)
     *
     * @param  array  $ids  ID 列表 (Student IDs 或 Staff IDs)
     * @param  string  $startDate  申請開始日期 (Y-m-d)
     * @param  string  $endDate  申請結束日期 (Y-m-d)
     * @param  mixed  $leaveType  假別
     * @param  string  $type  多型類別 (App\Models\Student 或 App\Models\Staff)
     * @param  string  $origin  來源
     * @param  string|null  $reason  理由
     */
    public function batchCreateRequests(
        array $ids,
        string $startDate,
        string $endDate,
        mixed $leaveType,
        string $type, // [新增]
        string $origin = 'liff',
        ?string $reason = null
    ): int {
        $count = 0;
        $start = Carbon::parse($startDate);
        $end = Carbon::parse($endDate);

        $leaveTypeValue = match (true) {
            $leaveType instanceof \BackedEnum => $leaveType->value,
            $leaveType instanceof \UnitEnum => $leaveType->name,
            default => (string) $leaveType,
        };

        foreach ($ids as $id) {
            // 檢查是否重複
            $exists = MealCancelRequest::where('cancellable_id', $id)
                ->where('cancellable_type', $type)
                ->where('start_date', $start->toDateString())
                ->where('end_date', $end->toDateString())
                ->exists();

            if ($exists) {
                continue;
            }

            MealCancelRequest::create([
                'cancellable_id' => $id,
                'cancellable_type' => $type,
                'start_date' => $start->toDateString(),
                'end_date' => $end->toDateString(),
                'leave_type' => $leaveTypeValue,
                'reason' => $reason,
                'status' => CancelRequestStatus::Pending,
                'origin' => $origin,
            ]);

            $count++;
        }

        return $count;
    }

    /**
     * 檢查某使用者在特定日期是否用餐 (Availability Check)
     * 用於輕量架構：用餐 = (訂閱) - (請假) - (假日)
     *
     * @todo 實作完整邏輯
     */
    public function isEating(User $user, Carbon $date): bool
    {
        // 暫時僅回傳 true，待後續 User/Student 關聯整合後實作
        return true;
    }
}
