<?php

namespace App\Services;

use App\Enums\Meals\CancelLeaveType;
use App\Enums\Meals\CancelRequestStatus;
use App\Models\MealCancelRequest;
use App\Models\User;
use Carbon\Carbon;

class MealService
{
    public function __construct(
        protected SemesterService $semesterService
    ) {}

    /**
     * 驗證停餐申請是否符合學期規則
     *
     * @param  \App\Models\Semester  $semester  所屬學期
     * @param  string  $startDate  申請開始日期 (Y-m-d)
     * @param  string  $endDate  申請結束日期 (Y-m-d)
     * @param  mixed  $leaveType  假別
     * @param  string|null  $scopeType  申請範圍類型
     *
     * @throws \Exception 如果不符合規則
     */
    public function validateCancellationRules(
        \App\Models\Semester $semester,
        string $startDate,
        string $endDate,
        mixed $leaveType,
        ?string $scopeType = null
    ): void {
        $start = Carbon::parse($startDate);
        $now = now();

        // 1. 檢查最少停餐「供餐日」天數 (Minimum Workdays)
        $workdays = $semester->countWorkdays($startDate, $endDate);

        // 區分個人與團體的最少天數限制
        $minDays = ($scopeType === 'class_activity' || $scopeType === 'group')
            ? $semester->group_min_stop_days
            : $semester->min_stop_days;

        if ($workdays < $minDays) {
            throw new \Exception("停餐天數(供餐日)至少需達 {$minDays} 天，目前僅 {$workdays} 天");
        }

        // 2. 根據假別取得提前天數 (Lead Time)
        $leadDays = match (true) {
            $leaveType instanceof \BackedEnum && $leaveType->value === 'sick' => $semester->sick_stop_days,
            $leaveType instanceof \BackedEnum && $leaveType->value === 'administrative' => $semester->public_leave_days,
            $leaveType === 'sick' => $semester->sick_stop_days,
            $leaveType === 'administrative' => $semester->public_leave_days,
            default => $semester->personal_stop_days,
        };

        // 3. 計算從「今天」到「申請首日」之間的「供餐日」數 (排除週末、國定假日、停課日)
        $servingDaysAhead = $semester->countServingDaysFromNow($start);

        if ($servingDaysAhead < $leadDays) {
            throw new \Exception("此假別需提前 {$leadDays} 個供餐日申請，目前僅提前 {$servingDaysAhead} 個供餐日");
        }

        // 4. 若剛好達到提前天數邊界，檢查是否已過截單時間
        if ($servingDaysAhead === $leadDays) {
            $cutOffTime = Carbon::parse($semester->stop_cut_off_time);
            $currentTime = $now->format('H:i:s');

            if ($currentTime >= $cutOffTime->format('H:i:s')) {
                throw new \Exception("已超過今日截單時間 {$semester->stop_cut_off_time}，請改為明日後的日期");
            }
        }
    }

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
     * @param  \App\Models\Semester|null  $semester  學期 (若為 null，自動查詢)
     * @param  string|null  $scopeType  申請範圍類型
     */
    public function batchCreateRequests(
        array $ids,
        string $startDate,
        string $endDate,
        mixed $leaveType,
        string $type,
        string $origin = 'liff',
        ?string $reason = null,
        ?\App\Models\Semester $semester = null,
        ?string $scopeType = null
    ): int {
        $count = 0;
        $start = Carbon::parse($startDate);
        $end = Carbon::parse($endDate);

        // 自動查詢學期（若未提供）
        if (! $semester) {
            $firstTarget = $type::find($ids[0]);
            if (! $firstTarget) {
                throw new \Exception('找不到申請對象');
            }
            $schoolId = $firstTarget->school_id;

            $semester = $this->semesterService->getSemesterByDate($startDate, $schoolId);

            if (! $semester) {
                $semester = $this->semesterService->getMostRelevantSemester($schoolId);
            }

            if (! $semester) {
                throw new \Exception('找不到該時段對應的啟用學期設定');
            }
        }

        // 執行業務規則驗證
        $this->validateCancellationRules($semester, $startDate, $endDate, $leaveType, $scopeType);

        $leaveTypeValue = match (true) {
            $leaveType instanceof \BackedEnum => $leaveType->value,
            $leaveType instanceof \UnitEnum => $leaveType->name,
            default => (string) $leaveType,
        };

        // 決定初始狀態 (考慮自動核准模式)
        $status = $semester->auto_approve_mode ? CancelRequestStatus::Approved : CancelRequestStatus::Pending;
        $approvedAt = $semester->auto_approve_mode ? now() : null;

        // 建立情境中包含的星期幾組合 (Carbon: 1=週一 ... 7=週日)
        $requestedDaysOfWeek = [];
        $current = $start->copy();
        while ($current->lte($end)) {
            $requestedDaysOfWeek[] = $current->dayOfWeekIso; // 1=Mon, 7=Sun
            $current->addDay();
        }

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

            // 對學生廢除「部分用餐」過濾
            $classroomId = null;
            if ($type === \App\Models\Student::class) {
                $student = $type::find($id);

                // A. 完全不吃午餐
                if (! $student?->eat_lunch) {
                    continue;
                }

                // B. 部分用餐：若 meal_days 有設定且　申請的日期範圍裡沒有任何一天屬於學生的用餐日，則跳過
                if (! empty($student->meal_days)) {
                    $mealDaysIso = array_map(fn ($d) => $d === 7 ? 7 : $d, $student->meal_days);
                    $hasOverlap = ! empty(array_intersect($requestedDaysOfWeek, $mealDaysIso));
                    if (! $hasOverlap) {
                        continue;
                    }
                }

                $classroomId = $student->classroom_id;
            }

            MealCancelRequest::create([
                'cancellable_id' => $id,
                'cancellable_type' => $type,
                'start_date' => $start->toDateString(),
                'end_date' => $end->toDateString(),
                'leave_type' => $leaveTypeValue,
                'scope_type' => $scopeType,
                'classroom_id' => $classroomId,
                'reason' => $reason,
                'status' => $status,
                'approved_at' => $approvedAt,
                'origin' => $origin,
            ]);

            $count++;
        }

        return $count;
    }

    /**
     * 班級活動停餐（批次建立該班所有學生）
     *
     * @param  \App\Models\Classroom  $classroom  班級
     * @param  string  $startDate  開始日期
     * @param  string  $endDate  結束日期
     * @param  \App\Models\User  $requestedBy  申請人 (老師)
     * @param  string|null  $reason  理由
     * @return int 建立數量
     */
    public function createClassActivityCancellation(
        \App\Models\Classroom $classroom,
        string $startDate,
        string $endDate,
        User $requestedBy,
        ?string $reason = null
    ): int {
        $start = Carbon::parse($startDate);
        $end = Carbon::parse($endDate);

        // 自動查詢並驗證學期規則
        $semester = $this->semesterService->getSemesterByDate($startDate, $classroom->school_id)
            ?? $this->semesterService->getMostRelevantSemester($classroom->school_id);

        if (! $semester) {
            throw new \Exception('找不到適用的學期設定');
        }

        // 執行業務規則驗證（公假規則 & 班級活動範圍）
        $this->validateCancellationRules($semester, $startDate, $endDate, CancelLeaveType::Administrative, 'class_activity');

        // 取得該班所有「在學」且「有搭伙」且「有吃午餐」的學生
        // (部分用餐過濾則在建立時檢查 meal_days 與日期範圍交集)
        $students = $classroom->students()
            ->active()
            ->where('is_meal_user', true)
            ->where('eat_lunch', true)
            ->get();

        if ($students->isEmpty()) {
            throw new \Exception('該班級目前沒有「在學且有搭伙且吃午餐」的學生，無需建立停餐申請');
        }

        // 建立情境涵蓋的星期幾組合 (ISO: 1=Mon, 7=Sun)
        $requestedDaysOfWeek = [];
        $current = $start->copy();
        while ($current->lte($end)) {
            $requestedDaysOfWeek[] = $current->dayOfWeekIso;
            $current->addDay();
        }

        // 決定初始狀態 (考慮自動核准模式)
        $status = $semester->auto_approve_mode ? CancelRequestStatus::Approved : CancelRequestStatus::Pending;
        $approvedAt = $semester->auto_approve_mode ? now() : null;

        $count = 0;
        foreach ($students as $student) {
            // 部分用餐過濾：若 meal_days 有設定，該學生必須在申請範圍內有至少一天屬於用餐日
            if (! empty($student->meal_days)) {
                $mealDaysIso = array_map(fn ($d) => $d === 7 ? 7 : $d, $student->meal_days);
                $hasOverlap = ! empty(array_intersect($requestedDaysOfWeek, $mealDaysIso));
                if (! $hasOverlap) {
                    continue;
                }
            }

            // 檢查是否重複
            $exists = MealCancelRequest::where('cancellable_id', $student->id)
                ->where('cancellable_type', \App\Models\Student::class)
                ->where('start_date', $start->toDateString())
                ->where('end_date', $end->toDateString())
                ->exists();

            if ($exists) {
                continue;
            }

            MealCancelRequest::create([
                'cancellable_id' => $student->id,
                'cancellable_type' => \App\Models\Student::class,
                'start_date' => $start->toDateString(),
                'end_date' => $end->toDateString(),
                'leave_type' => CancelLeaveType::Administrative->value,
                'scope_type' => 'class_activity',
                'classroom_id' => $classroom->id,
                'requested_by' => $requestedBy->id,
                'reason' => $reason ?? "班級活動停餐（{$classroom->full_name}）",
                'status' => $status,
                'approved_at' => $approvedAt,
                'origin' => 'liff',
            ]);

            $count++;
        }

        return $count;
    }

    /**
     * 跨班團體停餐（多選學生，可來自不同班級）
     *
     * @param  array  $studentIds  學生 ID 列表
     * @param  string  $startDate  開始日期
     * @param  string  $endDate  結束日期
     * @param  \App\Models\User  $requestedBy  申請人 (老師)
     * @param  string|null  $reason  理由
     * @return int 建立數量
     */
    public function createGroupCancellation(
        array $studentIds,
        string $startDate,
        string $endDate,
        User $requestedBy,
        ?string $reason = null
    ): int {
        $start = Carbon::parse($startDate);
        $end = Carbon::parse($endDate);

        // 自動查詢並驗證學期規則
        // 取得第一個學生的學校 ID (假設跨班學生屬於同一校，或至少由同一校管理者處理)
        $firstStudent = \App\Models\Student::find($studentIds[0]);
        $schoolId = $firstStudent ? $firstStudent->school_id : $requestedBy->staff?->school_id;

        $semester = $this->semesterService->getSemesterByDate($startDate, $schoolId)
            ?? $this->semesterService->getMostRelevantSemester($schoolId);

        if (! $semester) {
            throw new \Exception('找不到適用的學期設定');
        }

        // 執行業務規則驗證（公假規則 & 團體範圍）
        $this->validateCancellationRules($semester, $startDate, $endDate, CancelLeaveType::Administrative, 'group');

        // 建立情境涵蓋的星期幾 (ISO: 1=Mon, 7=Sun)
        $requestedDaysOfWeek = [];
        $current = $start->copy();
        while ($current->lte($end)) {
            $requestedDaysOfWeek[] = $current->dayOfWeekIso;
            $current->addDay();
        }

        // 決定初始狀態 (考慮自動核准模式)
        $status = $semester->auto_approve_mode ? CancelRequestStatus::Approved : CancelRequestStatus::Pending;
        $approvedAt = $semester->auto_approve_mode ? now() : null;

        $count = 0;
        foreach ($studentIds as $studentId) {
            $student = \App\Models\Student::find($studentId);

            // A. 完全不吃午餐 => 跳過
            if (! $student?->eat_lunch) {
                continue;
            }

            // B. 部分用餐：若 meal_days 與申請範圍沒有交集 => 跳過
            if (! empty($student->meal_days)) {
                $hasOverlap = ! empty(array_intersect($requestedDaysOfWeek, $student->meal_days));
                if (! $hasOverlap) {
                    continue;
                }
            }

            // 檢查是否重複
            $exists = MealCancelRequest::where('cancellable_id', $studentId)
                ->where('cancellable_type', \App\Models\Student::class)
                ->where('start_date', $start->toDateString())
                ->where('end_date', $end->toDateString())
                ->exists();

            if ($exists) {
                continue;
            }

            MealCancelRequest::create([
                'cancellable_id' => $studentId,
                'cancellable_type' => \App\Models\Student::class,
                'start_date' => $start->toDateString(),
                'end_date' => $end->toDateString(),
                'leave_type' => CancelLeaveType::Administrative->value,
                'scope_type' => 'group',
                'classroom_id' => $student->classroom_id,
                'requested_by' => $requestedBy->id,
                'reason' => $reason ?? '跨班團體停餐',
                'status' => $status,
                'approved_at' => $approvedAt,
                'origin' => 'liff',
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

    /**
     * 從校園食材登錄平台獲取菜單 (新實作：對接隱藏 API)
     *
     * @param  string  $schoolCode  6位數學校代碼 (MOE Code)
     * @param  string  $date  日期 (YYYY-MM-DD)
     * @return array|null 菜單資料陣列
     */
    public function getMenuFromFatrace(string $schoolCode, string $date): ?array
    {
        // 1. 取得 BatchDataId
        // API 參數 SchoolId 需移除首位 0 (例如 064616 -> 64616)
        $rawSchoolId = ltrim($schoolCode, '0');

        try {
            $metadataResponse = \Illuminate\Support\Facades\Http::get('https://fatraceschool.k12ea.gov.tw/offered/meal', [
                'SchoolId' => $rawSchoolId,
                'period' => $date,
                'KitchenId' => 'all',
                'MenuType' => 1,
            ]);

            if (! $metadataResponse->successful() || empty($metadataResponse->json('data'))) {
                return null;
            }

            $batchDataId = $metadataResponse->json('data.0.BatchDataId');
            $calorie = $metadataResponse->json('data.0.Calorie');

            // 2. 取得菜色列表
            $dishResponse = \Illuminate\Support\Facades\Http::get('https://fatraceschool.k12ea.gov.tw/dish', [
                'BatchDataId' => $batchDataId,
            ]);

            if (! $dishResponse->successful()) {
                return null;
            }

            return [
                'school_name' => $metadataResponse->json('data.0.SchoolName'),
                'date' => $date,
                'calorie' => $calorie,
                'dishes' => $dishResponse->json('data'),
                'source_url' => "https://fatraceschool.k12ea.gov.tw/frontend/search.html?school={$schoolCode}&period={$date}",
            ];
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Fatrace API Error: '.$e->getMessage());

            return null;
        }
    }
}
