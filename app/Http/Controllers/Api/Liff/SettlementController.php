<?php

namespace App\Http\Controllers\Api\Liff;

use App\Http\Controllers\Controller;
use App\Models\LunchSettlement;
use App\Models\Semester;
use App\Models\StaffCheckIn;
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
}
