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

        if (! $school) {
            return response()->json([
                'message' => '無效的 QR Code',
            ], 422);
        }

        // 2. Security Check: School Isolation
        // Must match user's assigned school_id
        if ($user->school_id !== $school->id) {
            return response()->json([
                'message' => '這不是您所屬單位的 QR Code，請勿跨校掃描',
            ], 403);
        }

        // 3. Record Check-in
        $checkIn = DB::transaction(function () use ($user, $school, $request) {
            return StaffCheckIn::create([
                'school_id' => $school->id,
                'user_id' => $user->id,
                'staff_id' => $user->staff->id ?? null, // Assuming User has one Staff record
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
