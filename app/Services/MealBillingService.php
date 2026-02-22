<?php

namespace App\Services;

use App\Models\Semester;
use Carbon\Carbon;
use Carbon\CarbonPeriod;

class MealBillingService
{
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
        if (! $semester->relationLoaded('exceptions')) {
            $semester->load('exceptions');
        }

        // 使用 CarbonPeriod 進行高效遍歷
        $period = CarbonPeriod::create($startDate->startOfDay(), $endDate->startOfDay());

        foreach ($period as $date) {
            // 1. 檢查是否為該年級/全校的「放假日」或「活動日(不供餐)」
            if ($this->isDayOffForGrade($semester, $date, $grade)) {
                continue;
            }

            // 2. 檢查是否符合學生的用餐模式 (例如週三不用餐)
            if ($mealPattern !== null) {
                // ISO day of week: 1 (Mon) to 7 (Sun)
                if (! in_array($date->dayOfWeekIso, $mealPattern)) {
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
    public function isDayOffForGrade(Semester $semester, Carbon $date, ?string $grade = null): bool
    {
        $dateStr = $date->toDateString();

        // A. 優先檢查學期例外表
        $exception = $semester->exceptions->first(fn ($e) => $e->date->toDateString() === $dateStr);

        if ($exception) {
            // 如果此例外適用於該年級 (或是全校通用)
            if ($exception->appliesToGrade($grade)) {
                // type 1 是補課供餐(非 DayOff)，其他 type (0, 2, 3, 4) 皆視為 DayOff
                return $exception->type !== 1;
            }
        }

        // B. 呼叫學期模型內建的檢查 (包含週末與國定假日)
        return $semester->isDayOff($date);
    }

    /**
     * 生成日曆矩陣數據
     * 回傳格格式為：[ '2025-09' => [ 'weeks' => [ [ ['date' => ..., 'is_meal_day' => ...] ] ] ] ]
     */
    public function generateCalendarMatrix(Semester $semester): array
    {
        $start = $semester->start_date->copy()->startOfMonth();
        $end = $semester->end_date->copy()->endOfMonth();

        $matrix = [];
        $currentMonth = $start->copy();

        while ($currentMonth->lte($end)) {
            $monthKey = $currentMonth->format('Y-m');
            $matrix[$monthKey] = [
                'label' => $currentMonth->format('n月'),
                'year' => $currentMonth->format('Y'),
                'month' => $currentMonth->format('n'),
                'weeks' => $this->buildMonthWeeks($semester, $currentMonth->copy()),
            ];
            $currentMonth->addMonth();
        }

        return $matrix;
    }

    /**
     * 建立單個月份的週矩陣 (含空格填充)
     */
    protected function buildMonthWeeks(Semester $semester, Carbon $month): array
    {
        $weeks = [];
        $startOfMonth = $month->copy()->startOfMonth();
        $endOfMonth = $month->copy()->endOfMonth();

        // 從該月第一天的那一週的第一天開始 (週日 = 0)
        $date = $startOfMonth->copy()->startOfWeek(Carbon::SUNDAY);
        $endOfCalendar = $endOfMonth->copy()->endOfWeek(Carbon::SATURDAY);

        while ($date->lte($endOfCalendar)) {
            $week = [];
            for ($i = 0; $i < 7; $i++) {
                $week[] = [
                    'date' => $date->copy(),
                    'is_current_month' => $date->month === $month->month,
                    'is_meal_day' => $date->between($semester->start_date, $semester->end_date) && ! $semester->isDayOff($date),
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
