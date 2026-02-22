<?php

namespace App\Http\Controllers\Api\Liff;

use App\Http\Controllers\Controller;
use App\Models\LunchSettlement;
use App\Models\Semester;
use App\Models\StaffCheckIn;
use App\Services\LunchSettlementService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SettlementController extends Controller
{
    /**
     * 取得當前教職員的結算與用餐明細
     */
    public function getCurrent(Request $request): JsonResponse
    {
        $user = $request->user();
        $staff = $user->staff;

        if (! $staff) {
            return response()->json(['message' => '找不到教職員資料'], 404);
        }

        $yearMonth = $request->query('year_month', now()->format('Y-m'));
        $parts = explode('-', $yearMonth);
        $year = (int) $parts[0];
        $month = (int) $parts[1];

        // 1. 優先找現成的結算紀錄 (通常是過去月份)
        $settlement = LunchSettlement::where('staff_id', $staff->id)
            ->where('year_month', $yearMonth)
            ->first();

        if ($settlement) {
            return response()->json([
                'year_month' => $settlement->year_month,
                'days_count' => $settlement->days_count,
                'amount' => $settlement->amount,
                'dates' => $settlement->check_in_dates,
                'status' => $settlement->status,
            ]);
        }

        // 2. 若無紀錄 (通常是當月份)，即時計算
        $startOfMonth = Carbon::create($year, $month, 1)->startOfDay();
        $endOfMonth = $startOfMonth->copy()->endOfMonth();

        // 尋找對應學期以取得餐費單價
        $semester = Semester::where('school_id', $staff->school_id)
            ->whereDate('start_date', '<=', $endOfMonth)
            ->whereDate('end_date', '>=', $startOfMonth)
            ->first();

        $mealPrice = $semester ? $semester->meal_price : 0;

        $checkIns = StaffCheckIn::where('staff_id', $staff->id)
            ->whereYear('checked_at', $year)
            ->whereMonth('checked_at', $month)
            ->orderBy('checked_at', 'desc')
            ->get();

        $uniqueDates = $checkIns->map(fn ($record) => $record->checked_at->toDateString())->unique()->values();
        $daysCount = $uniqueDates->count();

        return response()->json([
            'year_month' => $yearMonth,
            'days_count' => $daysCount,
            'amount' => $daysCount * $mealPrice,
            'dates' => $uniqueDates->toArray(),
            'status' => 'pending', // 當月尚未正式結算
        ]);
    }

    /**
     * 取得補登月曆資料
     * GET /api/settlement/backfill-calendar
     */
    public function getBackfillCalendar(Request $request): JsonResponse
    {
        $user = $request->user();
        $staff = $user->staff;

        if (! $staff) {
            return response()->json(['message' => '找不到教職員資料'], 404);
        }

        $yearMonth = $request->query('year_month', now()->format('Y-m'));
        $parts = explode('-', $yearMonth);
        $year = (int) $parts[0];
        $month = (int) $parts[1];

        $startOfMonth = Carbon::create($year, $month, 1)->startOfDay();
        $endOfMonth = $startOfMonth->copy()->endOfMonth();

        // 查找學期以取得 isDayOff 判斷
        $semester = Semester::where('school_id', $staff->school_id)
            ->whereDate('start_date', '<=', $endOfMonth)
            ->whereDate('end_date', '>=', $startOfMonth)
            ->first();

        // 產生月曆日期資料
        $calendarDays = [];
        $current = $startOfMonth->copy();
        while ($current->lte($endOfMonth)) {
            $dateStr = $current->toDateString();
            $isDayOff = true;

            if ($semester) {
                $isDayOff = $semester->isDayOff($current);
            } else {
                $isDayOff = $current->isWeekend();
            }

            $calendarDays[$dateStr] = [
                'date' => $dateStr,
                'day_of_week' => $current->dayOfWeekIso,
                'is_day_off' => $isDayOff,
                'is_future' => $current->gt(now()->endOfDay()),
            ];
            $current->addDay();
        }

        // 取得已簽到日期
        $checkIns = StaffCheckIn::where('staff_id', $staff->id)
            ->whereYear('checked_at', $year)
            ->whereMonth('checked_at', $month)
            ->get();

        $checkedDates = $checkIns->map(fn ($r) => $r->checked_at->toDateString())->unique()->values()->toArray();

        // 檢查是否已結算
        $settlement = LunchSettlement::where('staff_id', $staff->id)
            ->where('year_month', $yearMonth)
            ->first();

        $isLocked = $settlement && $settlement->status === 'paid';

        // 餐費單價
        $mealPrice = $semester ? $semester->meal_price : 0;

        return response()->json([
            'year_month' => $yearMonth,
            'calendar_days' => $calendarDays,
            'checked_dates' => $checkedDates,
            'is_locked' => $isLocked,
            'meal_price' => $mealPrice,
        ]);
    }

    /**
     * 提交補登
     * POST /api/settlement/backfill
     */
    public function submitBackfill(Request $request): JsonResponse
    {
        $user = $request->user();
        $staff = $user->staff;

        if (! $staff) {
            return response()->json(['message' => '找不到教職員資料'], 404);
        }

        $request->validate([
            'year_month' => 'required|string|regex:/^\d{4}-\d{2}$/',
            'add_dates' => 'array',
            'add_dates.*' => 'date',
            'remove_dates' => 'array',
            'remove_dates.*' => 'date',
        ]);

        try {
            $service = app(LunchSettlementService::class);
            $result = $service->processBackfill(
                staff: $staff,
                userId: $user->id,
                yearMonth: $request->year_month,
                addDates: $request->input('add_dates', []),
                removeDates: $request->input('remove_dates', []),
                ipAddress: $request->ip(),
            );

            $msg = "補登完成：新增 {$result['added']} 天，刪除 {$result['removed']} 天";
            if (count($result['skipped']) > 0) {
                $msg .= '。跳過：'.implode('、', $result['skipped']);
            }

            return response()->json([
                'status' => 'success',
                'message' => $msg,
                'days_count' => $result['days_count'],
                'amount' => $result['amount'],
                'checked_dates' => $result['checked_dates'],
            ]);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }
}
