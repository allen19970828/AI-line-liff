<?php

namespace App\Http\Controllers\Api\Liff;

use App\Enums\Meals\CancelLeaveType;
use App\Http\Controllers\Controller;
use App\Services\MealService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

/**
 * 停餐申請 API (給 LIFF 或外部 AI 工具使用)
 * 宗師規範：代碼極簡，邏輯委託給 Service。
 */
class MealCancellationController extends Controller
{
    public function __construct(
        protected MealService $mealService
    ) {}

    /**
     * 取得可停餐對象列表
     * GET /api/cancellation/targets
     */
    public function getTargets(Request $request)
    {
        $user = $request->user();
        $targets = [];

        // 1. 如果是教職員 (Staff)，可以幫自己停餐
        $staff = $user->staff; // 假設 User 有 hasOne Staff 關聯 (需確認)
        // 修正：User 模型中沒有 staff() 關聯，需透過 LineAccount 或直接查詢
        if (! $staff) {
            $staff = \App\Models\Staff::where('user_id', $user->id)->first();
        }

        if ($staff) {
            $targets[] = [
                'id' => $staff->id,
                'name' => $staff->name.' (我自己)',
                'type' => 'staff',
                'avatar_url' => $user->avatar_url,
            ];

            // 2. 如果是導師，可以幫班上學生停餐
            // 透過 classrooms 關聯取得學生
            $students = \App\Models\Student::whereIn('classroom_id', $staff->classrooms->pluck('id'))
                ->get();

            foreach ($students as $student) {
                $targets[] = [
                    'id' => $student->id,
                    'name' => $student->name,
                    'type' => 'student',
                    'class_name' => $student->class_full_name,
                ];
            }
        }

        // 3. (未來) 如果是家長，可以幫子女停餐
        // $children = $user->students; ...

        return response()->json([
            'status' => 'success',
            'data' => $targets,
        ]);
    }

    /**
     * 提交停餐申請
     * POST /api/meal-cancellation
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'target_id' => 'required', // ID
            'type' => 'required|in:student,staff', // 類型
            'date' => 'required|date|after_or_equal:today',
            'leave_type' => 'nullable|string',
            'reason' => 'nullable|string|max:200',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => '欄位格式錯誤',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $user = $request->user();
            $targetId = $request->target_id;
            $type = $request->type;

            // --- 權限檢查 ---
            $isAllowed = false;
            $modelClass = $type === 'staff' ? \App\Models\Staff::class : \App\Models\Student::class;
            $target = $modelClass::findOrFail($targetId);

            $staff = \App\Models\Staff::where('user_id', $user->id)->first();

            if ($type === 'staff') {
                // 停自己: 必須是本人
                if ($staff && $staff->id == $targetId) {
                    $isAllowed = true;
                }
            } elseif ($type === 'student') {
                // 停學生: 必須是該學生的導師 (或家長)
                if ($staff) {
                    // 檢查該學生是否在該老師的班級中
                    // 老師 -> Classrooms -> Students
                    $teacherClassroomIds = $staff->classrooms->pluck('id')->toArray();
                    if (in_array($target->classroom_id, $teacherClassroomIds)) {
                        $isAllowed = true;
                    }
                }
            }

            if (! $isAllowed) {
                return response()->json(['status' => 'error', 'message' => '無權限操作此對象'], 403);
            }

            // --- 執行停餐 ---
            $leaveType = $request->leave_type ? CancelLeaveType::from($request->leave_type) : CancelLeaveType::Personal;

            $count = $this->mealService->batchCreateRequests(
                [$targetId],
                $request->date,
                $request->date,
                $leaveType,
                $modelClass, // 傳入完整 Class Name
                'liff',
                $request->reason
            );

            return response()->json([
                'status' => 'success',
                'message' => '停餐申請已送出',
                'data' => [
                    'name' => $target->name,
                    'date' => $request->date,
                ],
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
            ], 400);
        }
    }
}
