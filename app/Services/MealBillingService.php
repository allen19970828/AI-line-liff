<?php

namespace App\Services;

use App\Models\Semester;
use Carbon\Carbon;
use Carbon\CarbonPeriod;

class MealBillingService
{
    /**
     * @var array<string, bool>|null 國定假日記憶體快取 (Hash Map)
     */
    protected ?array $nationalHolidaysCache = null;

    /**
     * 預熱國定假日快取，將指定範圍內的放假/補班日載入記憶體 Hash Map
     */
    public function warmupNationalHolidays(Carbon $startDate, Carbon $endDate): void
    {
        $holidays = \App\Models\NationalHoliday::inRange($startDate, $endDate)->get();

        $this->nationalHolidaysCache = [];
        foreach ($holidays as $holiday) {
            // 只在記憶體中記錄，讓查詢變成 O(1)
            // isset() 就能快速判斷這天是否有特殊設定
            $this->nationalHolidaysCache[$holiday->date->toDateString()] = [
                'type' => $holiday->type,
                'name' => $holiday->name,
            ];
        }
    }

    /**
     * 計算特定時段、特定年級、特定用餐模式的供餐日數
     *
     * @param  Semester  $semester  學期物件
     * @param  Carbon  $startDate  計算起始日
     * @param  Carbon  $endDate  計算結束日
     * @param  array|null  $mealPattern  用餐模式 (例如 [1, 2, 4, 5] 代表週三不用餐)，null 為全勤
     * @param  string|null  $grade  特定年級 (用於過濾年級專有的活動例外)
     */
    public function calculateMealDays(
        Semester $semester,
        Carbon $startDate,
        Carbon $endDate,
        ?array $mealPattern = null,
        ?string $grade = null
    ): int {
        $count = 0;

        // 確保例外資料已讀取
        if (!$semester->relationLoaded('exceptions')) {
            $semester->load('exceptions');
        }

        // [效能優化] 如果尚未預熱，自動幫忙預熱
        // 實務上最佳作法是在更高層級 (如 Controller 或 Job) 執行一次預熱
        if ($this->nationalHolidaysCache === null) {
            $this->warmupNationalHolidays($startDate, $endDate);
        }

        // 使用 CarbonPeriod 進行高效遍歷
        $period = CarbonPeriod::create($startDate->startOfDay(), $endDate->startOfDay());

        foreach ($period as $date) {
            // 1. 檢查是否為該年級/全校的「放假日」或「活動日(不供餐)」
            if ($this->isDayOffForGrade($semester, $date, $grade, $this->nationalHolidaysCache)) {
                continue;
            }

            // 2. 檢查是否符合學生的用餐模式 (例如週三不用餐)
            if ($mealPattern !== null) {
                // ISO day of week: 1 (Mon) to 7 (Sun)
                if (!in_array($date->dayOfWeekIso, $mealPattern)) {
                    continue;
                }
            }

            $count++;
        }

        return $count;
    }

    /**
     * 判斷某一天對特定年級是否為放假日
     * 考量了學期例外表 (SemesterException) 中的年級特定設定
     */
    public function isDayOffForGrade(Semester $semester, Carbon $date, ?string $grade = null, ?array $holidayCache = null): bool
    {
        // 呼叫經過 O(1) 快取優化且支援年級判斷的學期模型檢查
        return $semester->isDayOff($date, $holidayCache, $grade);
    }

    /**
     * 生成日曆矩陣數據
     * 回傳格格式為：[ '2025-09' => [ 'weeks' => [ [ ['date' => ..., 'is_meal_day' => ...] ] ] ] ]
     */
    public function generateCalendarMatrix(Semester $semester, ?array $holidayCache = null): array
    {
        /** @var \Carbon\Carbon $start */
        $start = $semester->start_date->copy()->startOfMonth();
        /** @var \Carbon\Carbon $end */
        $end = $semester->end_date->copy()->endOfMonth();

        // 如果未傳入快取且尚未預熱，則自動預熱
        if ($holidayCache === null && $this->nationalHolidaysCache === null) {
            $this->warmupNationalHolidays($start, $end);
            $holidayCache = $this->nationalHolidaysCache;
        } elseif ($holidayCache === null) {
            $holidayCache = $this->nationalHolidaysCache;
        }

        $matrix = [];
        $currentMonth = $start->copy();

        while ($currentMonth->lte($end)) {
            $monthKey = $currentMonth->format('Y-m');
            $matrix[$monthKey] = [
                'label' => $currentMonth->format('n月'),
                'year' => $currentMonth->format('Y'),
                'month' => $currentMonth->format('n'),
                'weeks' => $this->buildMonthWeeks($semester, $currentMonth->copy(), $holidayCache),
            ];
            $currentMonth->addMonth();
        }

        return $matrix;
    }

    /**
     * 建立單個月份的週矩陣 (含空格填充)
     */
    protected function buildMonthWeeks(Semester $semester, Carbon $month, ?array $holidayCache = null): array
    {
        $weeks = [];
        $startOfMonth = $month->copy()->startOfMonth();
        $endOfMonth = $month->copy()->endOfMonth();

        // 從該月第一天的那一週的第一天開始 (週日 = 0)
        $date = $startOfMonth->copy()->startOfWeek(\Carbon\CarbonInterface::SUNDAY);
        $endOfCalendar = $endOfMonth->copy()->endOfWeek(\Carbon\CarbonInterface::SATURDAY);

        while ($date->lte($endOfCalendar)) {
            $week = [];
            for ($i = 0; $i < 7; $i++) {
                $week[] = [
                    'date' => $date->copy(),
                    'is_current_month' => $date->month === $month->month,
                    'is_meal_day' => $date->between($semester->start_date, $semester->end_date) && !$semester->isDayOff($date, $holidayCache),
                    'day' => $date->day,
                    'date_string' => $date->toDateString(),
                ];
                $date->addDay();
            }
            $weeks[] = $week;
        }

        return $weeks;
    }
}
