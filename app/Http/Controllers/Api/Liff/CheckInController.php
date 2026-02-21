<?php

namespace App\Http\Controllers\Api\Liff;

use App\Http\Controllers\Controller;
use App\Models\School;
use App\Models\StaffCheckIn;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CheckInController extends Controller
{
    /**
     * Handle staff check-in from fixed QR code.
     */
    public function store(Request $request)
    {
        $request->validate([
            'code' => 'required|string',
        ]);

        $user = $request->user();

        $code = $request->code;

        // 宗師心法：如果掃到的是完整 URL，自動萃取裡面的 code 參數
        if (filter_var($code, FILTER_VALIDATE_URL)) {
            $parsedUrl = parse_url($code);
            if (isset($parsedUrl['query'])) {
                parse_str($parsedUrl['query'], $query);
                $code = $query['code'] ?? $code;
            }
        }

        // 1. Find school by checkin_code
        $school = School::where('checkin_code', $code)->first();
        $targetStaff = null;

        if (! $school) {
            // 宗師心法：如果沒找到學校，嘗試找個人用餐碼 (lunch_code)
            $targetStaff = \App\Models\Staff::where('lunch_code', $code)
                ->where('is_active', true)
                ->first();

            if ($targetStaff) {
                $school = $targetStaff->school;
            }
        }

        if (! $school) {
            return response()->json([
                'message' => '無效的 QR Code 或代碼已失效',
            ], 422);
        }

        // 2. Security Check: School Isolation
        // Must match user's assigned school_id (User scanning or Scanner being authenticated)
        if ($user->school_id !== $school->id) {
            return response()->json([
                'message' => '這不是您所屬單位的代碼，請勿跨校處理',
            ], 403);
        }

        // 3. Record Check-in
        $checkIn = DB::transaction(function () use ($user, $school, $targetStaff, $request) {
            return StaffCheckIn::create([
                'school_id' => $school->id,
                'user_id' => $targetStaff?->user_id ?? $user->id, // If scannned a staff, record for them. If scannned a school, record for current user.
                'staff_id' => $targetStaff?->id ?? $user->staff?->id,
                'checkin_code' => $request->code,
                'checked_at' => now(),
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ]);
        });

        return response()->json([
            'message' => '報到成功！',
            'school_name' => $school->name,
            'checked_at' => $checkIn->checked_at->toDateTimeString(),
        ]);
    }
}
