<?php

namespace App\Services;

use App\Models\SchoolCalendar; // 確保有引用
use App\Models\Semester;
use Carbon\Carbon;

class SemesterService
{
    // 快取時間：學期不常變，設長一點 (1 天)
    protected const CACHE_TTL = 86400;

    /**
     * 根據開學日，自動推算學期名稱與代碼
     */
    public function calculateDetails(string $startDate): array
    {
        $date = Carbon::parse($startDate);
        $year = $date->year;
        $month = $date->month;

        // 台灣學制：8月1日為新學年開始
        // 8月~1月 => 上學期 (code 1)
        // 2月~7月 => 下學期 (code 2)

        $isFirstSemester = ($month >= 8);

        $rocYear = $year - 1911;
        $academicYear = $isFirstSemester ? $rocYear : ($rocYear - 1);
        $semesterCode = $isFirstSemester ? 1 : 2;

        // 推算建議結束日
        if ($isFirstSemester) {
            // 上學期：跨年到明年1月底
            $endDate = $date->copy()->addYear()->month(1)->endOfMonth();
        } else {
            // 下學期：當年7月底
            $endDate = $date->copy()->month(7)->endOfMonth();
        }

        return [
            'name' => "{$academicYear}學年度第{$semesterCode}學期",
            'code' => "{$academicYear}-{$semesterCode}",
            'suggested_end_date' => $endDate->format('Y-m-d'),
        ];
    }

    /**
     * 學期重疊檢查
     */
    public function hasOverlap(int $schoolId, ?string $start, ?string $end, ?int $ignoreId = null): bool
    {
        if (! $start || ! $end) {
            return false;
        }

        $query = Semester::where('school_id', $schoolId)
            ->where(function ($q) use ($start, $end) {
                $q->whereBetween('start_date', [$start, $end])
                    ->orWhereBetween('end_date', [$start, $end])
                    ->orWhere(function ($sub) use ($start, $end) {
                        $sub->where('start_date', '<=', $start)
                            ->where('end_date', '>=', $end);
                    });
            });

        if ($ignoreId) {
            $query->where('id', '!=', $ignoreId);
        }

        return $query->exists();
    }

    /**
     * 初始化學期行事曆
     * 自動填入供餐日 (週一~週五)
     */
    public function initializeCalendar(Semester $semester): void
    {
        $startDate = Carbon::parse($semester->start_date);
        $endDate = Carbon::parse($semester->end_date);

        $period = \Carbon\CarbonPeriod::create($startDate, $endDate);

        $upsertData = [];
        $batchSize = 100; // 批次大小

        foreach ($period as $date) {
            $isWeekend = $date->isWeekend();

            $upsertData[] = [
                'school_id' => $semester->school_id,
                'semester_id' => $semester->id,
                'date' => $date->format('Y-m-d'),
                'is_meal_day' => ! $isWeekend, // 週末不供餐
                'description' => null,
                'show_in_reports' => true,
                // created_at/updated_at 需手動給值，因為 upsert 不會自動填
                'created_at' => now(),
                'updated_at' => now(),
            ];

            if (count($upsertData) >= $batchSize) {
                $this->batchInsertCalendar($upsertData);
                $upsertData = [];
            }
        }

        // 處理剩餘的
        if (! empty($upsertData)) {
            $this->batchInsertCalendar($upsertData);
        }
    }

    /**
     * 根據特定日期尋找學期 (精確匹配)
     */
    public function getSemesterByDate(string|Carbon $date, int $schoolId): ?Semester
    {
        $d = ($date instanceof Carbon) ? $date->toDateString() : $date;

        return Semester::where('school_id', $schoolId)
            ->whereDate('start_date', '<=', $d)
            ->whereDate('end_date', '>=', $d)
            ->active()
            ->first();
    }

    /**
     * 取得最相關的學期 (智慧導向)
     * 1. 優先找「正在進行中」的學期
     * 2. 若無，找「下一個即將開始」的學期 (寒暑假預填模式)
     */
    public function getMostRelevantSemester(int $schoolId): ?Semester
    {
        $today = now()->toDateString();

        // Step 1: 優先找現在
        $current = $this->getSemesterByDate($today, $schoolId);
        if ($current) {
            return $current;
        }

        // Step 2: 找未來最近的一個
        return Semester::where('school_id', $schoolId)
            ->whereDate('start_date', '>', $today)
            ->active()
            ->orderBy('start_date', 'asc')
            ->first();
    }

    protected function batchInsertCalendar(array $data): void
    {
        // 防呆：先檢查 SchoolCalendar 是否存在，若無則跳過以免報錯
        if (class_exists(SchoolCalendar::class)) {
            SchoolCalendar::upsert(
                $data,
                ['school_id', 'date'], // Unique Index
                ['semester_id', 'is_meal_day', 'updated_at']
            );
        }
    }
}
