<?php

namespace App\Services;

use App\Models\LunchSettlement;
use App\Models\School;
use App\Models\Semester;
use App\Models\Staff;
use App\Models\StaffCheckIn;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class LunchSettlementService
{
    /**
     * 當日用餐截止時間 — 超過此時間才能補登當天
     */
    private const BACKFILL_CUTOFF_TIME = '12:40';

    /**
     * 計算並生成指定月份的結算單
     *
     * @param  string  $yearMonth  format: YYYY-MM
     * @return Collection<LunchSettlement>
     */
    public function calculateMonthlySettlement(School $school, string $yearMonth): Collection
    {
        $parts = explode('-', $yearMonth);
        if (count($parts) !== 2) {
            throw new \InvalidArgumentException('Invalid yearMonth format. Expected YYYY-MM.');
        }
        $year = (int) $parts[0];
        $month = (int) $parts[1];

        // 1. 找出該月對應的學期 (Semester)
        $startOfMonth = Carbon::create($year, $month, 1)->startOfDay();
        $endOfMonth = $startOfMonth->copy()->endOfMonth();

        $semester = Semester::where('school_id', $school->id)
            ->whereDate('start_date', '<=', $endOfMonth)
            ->whereDate('end_date', '>=', $startOfMonth)
            ->first();

        if (! $semester) {
            return collect();
        }

        // 2. 撈取該校該月所有教職員的簽到紀錄
        $checkIns = StaffCheckIn::where('school_id', $school->id)
            ->whereYear('checked_at', $year)
            ->whereMonth('checked_at', $month)
            ->get();

        // 3. 依 Staff 分群計算
        $grouped = $checkIns->groupBy('staff_id');

        $settlements = collect();

        foreach ($grouped as $staffId => $records) {
            if (! $staffId) {
                continue;
            }

            $staff = Staff::find($staffId);
            if (! $staff) {
                continue;
            }

            // 計算天數 (Distinct Date)
            $uniqueDates = $records->map(function ($record) {
                return $record->checked_at->toDateString();
            })->unique()->values()->toArray();

            $daysCount = count($uniqueDates);
            $amount = $daysCount * $semester->meal_price;

            // 4. Update or Create Settlement
            $settlement = LunchSettlement::updateOrCreate(
                [
                    'school_id' => $school->id,
                    'staff_id' => $staff->id,
                    'year_month' => $yearMonth,
                ],
                [
                    'semester_id' => $semester->id,
                    'days_count' => $daysCount,
                    'amount' => $amount,
                    'check_in_dates' => $uniqueDates,
                    'snapshot_price' => $semester->meal_price,
                ]
            );

            $settlements->push($settlement);
        }

        return $settlements;
    }

    /**
     * 處理補登作業 (核心業務邏輯)
     *
     * @return array{added: int, removed: int, skipped: string[], days_count: int, amount: int, checked_dates: string[]}
     *
     * @throws \Exception 當月份不允許補登或已結清時
     */
    public function processBackfill(
        Staff $staff,
        int $userId,
        string $yearMonth,
        array $addDates = [],
        array $removeDates = [],
        ?string $ipAddress = null,
    ): array {
        $parts = explode('-', $yearMonth);
        $year = (int) $parts[0];
        $month = (int) $parts[1];

        // 1. 檢查是否只允許當月或前月
        $this->validateBackfillMonth($yearMonth);

        // 2. 檢查是否已結清
        $this->checkSettlementLock($staff->id, $yearMonth);

        // 3. 查找學期
        $semester = $this->findSemesterForMonth($staff->school_id, $year, $month);

        // 4. 處理新增補登
        $addResult = $this->addBackfillDates(
            $staff,
            $userId,
            $yearMonth,
            $addDates,
            $semester,
            $ipAddress
        );

        // 5. 處理刪除補登
        $removed = $this->removeBackfillDates($staff->id, $yearMonth, $removeDates);

        // 6. 重新計算結算數據
        $summary = $this->recalculateMonthSummary($staff->id, $year, $month, $semester);

        return [
            'added' => $addResult['added'],
            'removed' => $removed,
            'skipped' => $addResult['skipped'],
            'days_count' => $summary['days_count'],
            'amount' => $summary['amount'],
            'checked_dates' => $summary['checked_dates'],
        ];
    }

    /**
     * 驗證補登月份是否合法 (當月或前月)
     */
    private function validateBackfillMonth(string $yearMonth): void
    {
        $currentYearMonth = now()->format('Y-m');
        $lastMonth = now()->subMonth()->format('Y-m');

        if ($yearMonth !== $currentYearMonth && $yearMonth !== $lastMonth) {
            throw new \Exception('只能補登當月或前月的用餐紀錄');
        }
    }

    /**
     * 檢查該月結算是否已鎖定 (已結清則不可修改)
     */
    private function checkSettlementLock(int $staffId, string $yearMonth): void
    {
        $settlement = LunchSettlement::where('staff_id', $staffId)
            ->where('year_month', $yearMonth)
            ->first();

        if ($settlement && $settlement->status === 'paid') {
            throw new \Exception('本月已結清，無法修改');
        }
    }

    /**
     * 查找指定月份對應的學期
     */
    private function findSemesterForMonth(int $schoolId, int $year, int $month): ?Semester
    {
        $startOfMonth = Carbon::create($year, $month, 1)->startOfDay();
        $endOfMonth = $startOfMonth->copy()->endOfMonth();

        return Semester::where('school_id', $schoolId)
            ->whereDate('start_date', '<=', $endOfMonth)
            ->whereDate('end_date', '>=', $startOfMonth)
            ->first();
    }

    /**
     * 處理新增補登日期
     *
     * @return array{added: int, skipped: string[]}
     */
    private function addBackfillDates(
        Staff $staff,
        int $userId,
        string $yearMonth,
        array $addDates,
        ?Semester $semester,
        ?string $ipAddress,
    ): array {
        $added = 0;
        $skipped = [];

        foreach ($addDates as $dateStr) {
            $date = Carbon::parse($dateStr);

            // 驗證日期在月份範圍內
            if ($date->format('Y-m') !== $yearMonth) {
                continue;
            }

            // 驗證不是非供餐日
            if ($semester && $semester->isDayOff($date)) {
                continue;
            }

            // 當天日期：必須過截止時間才能補登
            if ($date->isToday() && now()->format('H:i') < self::BACKFILL_CUTOFF_TIME) {
                $skipped[] = $dateStr.'（未過 '.self::BACKFILL_CUTOFF_TIME.'）';

                continue;
            }

            // 不能是未來日期
            if ($date->gt(now()->endOfDay())) {
                continue;
            }

            // 檢查是否已存在
            $exists = StaffCheckIn::where('staff_id', $staff->id)
                ->whereDate('checked_at', $date->toDateString())
                ->exists();

            if ($exists) {
                $skipped[] = $dateStr.'（已登記）';

                continue;
            }

            StaffCheckIn::create([
                'school_id' => $staff->school_id,
                'user_id' => $userId,
                'staff_id' => $staff->id,
                'checkin_code' => 'backfill',
                'checked_at' => $date->setTime(12, 0, 0),
                'ip_address' => $ipAddress,
                'user_agent' => 'LIFF-Backfill',
            ]);
            $added++;
        }

        return ['added' => $added, 'skipped' => $skipped];
    }

    /**
     * 處理刪除補登日期 (只刪除 backfill 類型，保護 QR 簽到)
     */
    private function removeBackfillDates(int $staffId, string $yearMonth, array $removeDates): int
    {
        $removed = 0;

        foreach ($removeDates as $dateStr) {
            $date = Carbon::parse($dateStr);

            if ($date->format('Y-m') !== $yearMonth) {
                continue;
            }

            $deleted = StaffCheckIn::where('staff_id', $staffId)
                ->whereDate('checked_at', $date->toDateString())
                ->where('checkin_code', 'backfill')
                ->delete();

            $removed += $deleted;
        }

        return $removed;
    }

    /**
     * 重新計算該月的結算數據
     *
     * @return array{days_count: int, amount: int, checked_dates: string[]}
     */
    private function recalculateMonthSummary(int $staffId, int $year, int $month, ?Semester $semester): array
    {
        $checkIns = StaffCheckIn::where('staff_id', $staffId)
            ->whereYear('checked_at', $year)
            ->whereMonth('checked_at', $month)
            ->get();

        $uniqueDates = $checkIns->map(fn ($r) => $r->checked_at->toDateString())->unique()->values();
        $daysCount = $uniqueDates->count();
        $mealPrice = $semester ? $semester->meal_price : 0;

        return [
            'days_count' => $daysCount,
            'amount' => $daysCount * $mealPrice,
            'checked_dates' => $uniqueDates->toArray(),
        ];
    }
}
