<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class CheckInController extends Controller
{
    /**
     * Handle QR Code Check-in
     * POST /api/check-in
     */
    public function store(Request $request)
    {
        $request->validate([
            'code' => 'required|string',
        ]);

        $user = $request->user();
        $code = trim($request->input('code'));

        // 1. Verify QR Code Format & School Match
        // Format: LUNCH_CHECKIN_{SCHOOL_CODE}
        // Example: LUNCH_CHECKIN_TEST001
        $school = $user->school;
        if (! $school) {
            return response()->json(['message' => '您尚未綁定任何學校'], 400);
        }

        $expectedCode = 'LUNCH_CHECKIN_'.$school->code;

        if ($code !== $expectedCode) {
            // Check if it's at least a valid check-in code for *another* school?
            // Or just generic invalid code.
            if (str_starts_with($code, 'LUNCH_CHECKIN_')) {
                return response()->json(['message' => '請掃描【本校】的專屬條碼'], 400);
            }

            return response()->json(['message' => '無效的條碼格式'], 400);
        }

        // 2. Identify Staff/Student Record
        $staff = \App\Models\SchoolStaff::where('user_id', $user->id)
            ->where('school_id', $school->id)
            ->first();

        if (! $staff) {
            return response()->json(['message' => '找不到您的教職員資料'], 404);
        }

        // 3. Check Eligibility (Strict Mode)
        // Rule: Must be `is_meal_user` OR have a specific `daily_orders` record for today
        // For simple MVP: we trust `is_meal_user` first.

        $today = now()->format('Y-m-d');

        // Check if explicitly cancelled / stopped? (Future feature)
        // For now, check the global setting
        if (! $staff->is_meal_user) {
            return response()->json([
                'message' => '您未參與午餐，若有訂餐請至個人設定進行修正',
            ], 403);
        }

        // 4. Record Check-in (Create or Update Daily Order)
        try {
            // Get Current Semester
            // TODO: Use helper or scope for "Current Active Semester"
            $semester = \App\Models\Semester::where('school_id', $school->id)
                ->latest('start_date')
                ->first();

            if (! $semester) {
                return response()->json(['message' => '找不到目前學期設定，無法簽到'], 500);
            }

            // Find or Create Daily Order for today
            // We use `firstOrCreate` to handle the case where the order wasn't pre-generated
            $order = \App\Models\DailyOrder::firstOrCreate(
                [
                    'school_id' => $school->id,
                    'semester_id' => $semester->id,
                    'school_staff_id' => $staff->id,
                    'date' => $today,
                    'meal_type' => 'lunch',
                ],
                [
                    'status' => 'ordered',
                    // 'amount_user' => ... calculate price if needed
                ]
            );

            // Check if already checked in
            if ($order->taken_at) {
                return response()->json([
                    'message' => '您今天已經簽到過了 ('.$order->taken_at->format('H:i').')',
                    'timestamp' => $order->taken_at->toDateTimeString(),
                ]);
            }

            // Update Status
            $order->update([
                'status' => 'taken', // or 'eaten'
                'taken_at' => now(),
                'taken_method' => 'qr_scan',
            ]);

            return response()->json([
                'message' => '簽到成功！祝您用餐愉快 🍱',
                'location' => $school->name.' 餐廳',
                'timestamp' => now()->toDateTimeString(),
            ]);

        } catch (\Exception $e) {
            Log::error("Check-in Error User {$user->id}: ".$e->getMessage());

            return response()->json(['message' => '系統錯誤，請通知管理員'], 500);
        }
    }
}
