<?php

namespace App\Http\Controllers\Api\Liff;

use App\Http\Controllers\Controller;
use App\Models\MealComplaint;
use App\Models\Staff;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class MealComplaintController extends Controller
{
    /**
     * 取得回報歷史
     */
    public function index(Request $request)
    {
        $user = $request->user();
        $staff = Staff::where('user_id', $user->id)->first();

        if (! $staff) {
            return response()->json(['status' => 'error', 'message' => '找不到教職員資料'], 403);
        }

        $complaints = MealComplaint::where('staff_id', $staff->id)
            ->with(['classroom', 'school'])
            ->latest()
            ->get()
            ->map(fn ($c) => [
                'id' => $c->id,
                'occurred_at' => $c->occurred_at->toDateString(),
                'classroom_name' => $c->classroom?->class_name ?? '全校性',
                'content' => $c->content,
                'photo_url' => $c->photo_path ? Storage::disk('public')->url($c->photo_path) : null,
                'status' => $c->status,
                'status_text' => match ($c->status) {
                    'pending' => '待處理',
                    'processing' => '處理中',
                    'resolved' => '已結案',
                    default => '未知',
                },
                'caterer_reply' => $c->caterer_reply,
                'created_at' => $c->created_at->toDateTimeString(),
            ]);

        return response()->json([
            'status' => 'success',
            'data' => $complaints,
        ]);
    }

    /**
     * 提交新回報
     */
    public function store(Request $request)
    {
        $user = $request->user();
        $staff = Staff::where('user_id', $user->id)->first();

        if (! $staff) {
            return response()->json(['status' => 'error', 'message' => '找不到教職員資料'], 403);
        }

        $validator = Validator::make($request->all(), [
            'classroom_id' => 'nullable|exists:school_classrooms,id', // 修正為實際的表名 school_classrooms
            'occurred_at' => 'required|date',
            'content' => 'required|string|min:2',
            'photo' => 'nullable|image|max:10240', // 10MB max
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => '驗證失敗',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $photoPath = null;
            if ($request->hasFile('photo')) {
                $photoPath = $request->file('photo')->store('complaints', 'public');
            }

            $complaint = MealComplaint::create([
                'school_id' => $staff->school_id,
                'classroom_id' => $request->classroom_id,
                'staff_id' => $staff->id,
                'occurred_at' => $request->occurred_at,
                'content' => $request->content,
                'photo_path' => $photoPath,
                'status' => 'pending',
            ]);

            // 🆕 通知午餐秘書 (寫入彙整日誌)
            $schoolName = $staff->school?->name ?? '未知學校';
            $logMsg = "【新意見回報】\n學校：{$schoolName}\n回報者：{$staff->name}\n描述：{$request->content}\n時間：".now()->toDateTimeString();
            \Illuminate\Support\Facades\Log::channel('daily')->info($logMsg);

            return response()->json([
                'status' => 'success',
                'message' => '反映意見已送出，感謝您的回報',
                'data' => $complaint,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => '發生意外錯誤：'.$e->getMessage(),
            ], 500);
        }
    }
}
