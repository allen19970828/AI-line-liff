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
        // 邏輯修正：只要學期時間與該月份有重疊即可，不一定要包含 1 號
        $startOfMonth = Carbon::create($year, $month, 1)->startOfDay();
        $endOfMonth = $startOfMonth->copy()->endOfMonth();

        $semester = Semester::where('school_id', $school->id)
            ->whereDate('start_date', '<=', $endOfMonth)
            ->whereDate('end_date', '>=', $startOfMonth)
            ->first();

        // 強制處理：若無學期，暫時無法計算，回傳空
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
                    // status 不重置，保留付款狀態
                ]
            );

            $settlements->push($settlement);
        }

        return $settlements;
    }
}
