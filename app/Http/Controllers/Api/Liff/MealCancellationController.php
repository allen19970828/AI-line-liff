<?php

namespace App\Http\Controllers\Api\Liff;

use App\Enums\Meals\CancelLeaveType;
use App\Http\Controllers\Controller;
use App\Models\NationalHoliday;
use App\Models\SemesterException;
use App\Models\Staff;
use App\Models\Student;
use App\Services\MealService;
use App\Services\SemesterService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

/**
 * 停餐申請 API (給 LIFF 或外部 AI 工具使用)
 * 宗師規範：代碼極簡，邏輯委託給 Service。
 */
class MealCancellationController extends Controller
{
    public function __construct(
        protected MealService $mealService,
        protected SemesterService $semesterService
    ) {
    }

    /**
     * 取得可停餐對象列表
     * GET /api/cancellation/targets
     */
    public function getTargets(Request $request)
    {
        $user = $request->user();
        $targets = [];

        // 1. 識別身分
        $staff = $user->staff; // 使用 Phase 10 新增的關聯

        if ($staff) {
            // A. 自己 (Staff 本人)
            // [宗師指點] 依據學期設定，決定是否允許教職員停餐
            // daily (按日) => 不用停餐 (因為本身就是有吃才算錢，沒吃不算) -> 隱藏
            // monthly (包月) => 需要停餐 (因為預收了整月，停餐要退費) -> 顯示

            // 使用 SemesterService 取得最相關的學期 (考量寒暑假預填)
            $relevantSemester = $this->semesterService->getMostRelevantSemester($staff->school_id);

            $shouldShowStaff = true;
            if ($relevantSemester && $relevantSemester->staff_meal_mode === \App\Enums\StaffMealMode::Daily) {
                $shouldShowStaff = false;
            }

            if ($shouldShowStaff) {
                $targets[] = [
                    'id' => $staff->id,
                    'name' => $staff->name . ' (我自己)',
                    'type' => 'staff',
                    'avatar_url' => $user->avatar_url,
                ];
            }

            // B. 學生 (根據權限)
            $studentsQuery = Student::query()->active();

            // 規則：如果是 Admin 或 超管，看全校；否則只看自己班級
            if ($staff->staff_role === \App\Enums\StaffRole::Admin || $user->canBypassSchoolScope()) {
                // Admin: 看全校 (且符合該校 scope)
                $students = $studentsQuery->get();
            } else {
                // Teacher: 只看導師班級
                $classrooms = $staff->classrooms->pluck('id');
                if ($classrooms->isNotEmpty()) {
                    $students = $studentsQuery->whereIn('classroom_id', $classrooms)->get();
                } else {
                    $students = collect();
                }
            }

            foreach ($students as $student) {
                // 為了前端顯示方便，標註是否為全校學生 (Admin view)
                $targets[] = [
                    'id' => $student->id,
                    'name' => $student->name,
                    'type' => 'student',
                    'class_name' => $student->class_full_name,
                ];
            }
        }

        // 取得班級列表 (供前端選單使用)
        $classrooms = [];
        $permissions = [
            'can_access_group' => false,
            'can_class_activity' => false,
            'can_cross_class' => false,
        ];

        if ($staff) {
            $permissions = [
                'can_access_group' => true, // 放寬給所有教職員使用
                'can_class_activity' => true, // 放寬給所有教職員使用
                'can_cross_class' => true, // 放寬給所有教職員使用
            ];

            $classrooms = \App\Models\Classroom::query()
                ->when(!$user->canBypassSchoolScope(), function ($q) use ($staff) {
                    $q->where('school_id', $staff->school_id);
                })
                ->active()
                ->orderBy('sort_order')
                ->get()
                ->map(fn($c) => [
                    'id' => $c->id,
                    'name' => $c->class_name, // 客製化：只顯示班級名稱 (因 user 反映 full_name 會重複顯示年級)
                ]);
        }

        return response()->json([
            'status' => 'success',
            'data' => $targets,
            'classrooms' => $classrooms,
            'permissions' => $permissions,
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
            'start_date' => 'required|date|after_or_equal:today',
            'end_date' => 'nullable|date|after_or_equal:start_date',
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
            $startDate = $request->start_date;
            $endDate = $request->end_date ?? $startDate;

            // --- 權限檢查 ---
            $isAllowed = false;
            $modelClass = $type === 'staff' ? Staff::class : Student::class;
            $target = $modelClass::findOrFail($targetId);

            $staff = Staff::where('user_id', $user->id)->first();

            if ($type === 'staff') {
                // 停自己: 必須是本人
                if ($staff && $staff->id == $targetId) {
                    $isAllowed = true;
                }
            } elseif ($type === 'student') {
                // 停學生: 必須是該學生的導師 (或家長) 或 行政人員
                if ($staff) {
                    // Admin or Super Admin: Pass
                    if ($staff->staff_role === \App\Enums\StaffRole::Admin || $user->canBypassSchoolScope()) {
                        $isAllowed = true;
                    } else {
                        // Teacher: 檢查該學生是否在該老師的班級中
                        $teacherClassroomIds = $staff->classrooms->pluck('id')->toArray();
                        if (in_array($target->classroom_id, $teacherClassroomIds)) {
                            $isAllowed = true;
                        }
                    }
                }
            }

            if (!$isAllowed) {
                return response()->json(['status' => 'error', 'message' => '無權限操作此對象'], 403);
            }

            // --- 執行停餐 ---
            $leaveType = $request->leave_type ? CancelLeaveType::from($request->leave_type) : CancelLeaveType::Personal;

            $count = $this->mealService->batchCreateRequests(
                [$targetId],
                $startDate,
                $endDate,
                $leaveType,
                $modelClass, // 傳入完整 Class Name
                'liff',
                $request->reason
            );

            return response()->json([
                'status' => 'success',
                'message' => '停餐申請已送出',
                'count' => $count,
                'affected_students' => [
                    [
                        'id' => $target->id,
                        'name' => $target->name,
                        'class_name' => $target->class_full_name ?? '',
                        'seat_number' => $target->seat_number ?? '',
                    ],
                ],
                'data' => [
                    'name' => $target->name,
                    'start_date' => $startDate,
                    'end_date' => $endDate,
                ],
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * 搜尋學生 (透過 班級ID + 座號)
     * POST /api/cancellation/search-student
     */
    public function searchStudent(Request $request)
    {
        $request->validate([
            'classroom_id' => 'required|integer',
            'seat_number' => 'required|integer',
        ]);

        $user = $request->user();
        $staff = $user->staff;

        if (!$staff) {
            return response()->json(['status' => 'error', 'message' => '找不到您的教職員資料'], 404);
        }

        // 查詢邏輯
        try {
            // 1. 先找班級 (確保該班級屬於該校)
            $classroom = \App\Models\Classroom::query()
                ->where('id', $request->classroom_id)
                ->when(!$user->canBypassSchoolScope(), function ($q) use ($staff) {
                    $q->where('school_id', $staff->school_id);
                })
                ->active()
                ->first();

            if (!$classroom) {
                return response()->json(['status' => 'error', 'message' => '找不到該班級'], 404);
            }

            // 2. 找學生
            $student = $classroom->students()
                ->where('seat_number', $request->seat_number)
                ->active()
                ->first();

            if (!$student) {
                return response()->json(['status' => 'error', 'message' => '找不到該座號的學生'], 404);
            }

            return response()->json([
                'status' => 'success',
                'data' => [
                    'id' => $student->id,
                    'name' => $student->name,
                    'class_name' => $classroom->full_name,
                    'seat_number' => $student->seat_number,
                    'is_meal_user' => $student->is_meal_user,
                ],
            ]);

        } catch (\Exception $e) {
            return response()->json(['status' => 'error', 'message' => '查詢失敗: ' . $e->getMessage()], 500);
        }
    }

    /**
     * 班級活動停餐（批次建立該班所有學生）
     * POST /api/meal-cancellation/class-activity
     */
    public function storeClassActivity(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'classroom_id' => 'required|exists:classrooms,id',
            'start_date' => 'required|date|after_or_equal:today',
            'end_date' => 'required|date|after_or_equal:start_date',
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
            $staff = $user->staff;

            if (!$staff) {
                return response()->json(['status' => 'error', 'message' => '找不到您的教職員資料'], 404);
            }

            // 取得班級 (確保為有效班級)
            $classroom = \App\Models\Classroom::query()
                ->active()
                ->findOrFail($request->classroom_id);

            // [宗師指點] 權限檢查：必須是該班導師 或 行政人員/超管
            $isHomeRoomTeacher = $staff->classrooms->contains('id', $classroom->id);
            $isAdmin = $staff->staff_role === \App\Enums\StaffRole::Admin || $user->canBypassSchoolScope();

            if (!$isHomeRoomTeacher && !$isAdmin) {
                return response()->json([
                    'status' => 'error',
                    'message' => '您不是該班級導師，且不具備行政權限，無法申請班級活動停餐',
                ], 403);
            }

            // 呼叫 Service 層方法
            $count = $this->mealService->createClassActivityCancellation(
                classroom: $classroom,
                startDate: $request->start_date,
                endDate: $request->end_date,
                requestedBy: $user,
                reason: $request->reason
            );

            // [效能優化] 不再回傳詳細名單，避免前端渲染負擔
            return response()->json([
                'status' => 'success',
                'message' => '班級活動停餐申請成功',
                'count' => $count,
                // 'affected_students' => $students, // 已移除
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * 跨班團體停餐（多選學生）
     * POST /api/meal-cancellation/group
     */
    public function storeGroupCancellation(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'student_ids' => 'required|array|min:1',
            'student_ids.*' => 'exists:students,id',
            'start_date' => 'required|date|after_or_equal:today',
            'end_date' => 'required|date|after_or_equal:start_date',
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
            $staff = $user->staff;

            if (!$staff) {
                return response()->json(['status' => 'error', 'message' => '找不到您的教職員資料'], 404);
            }

            // [宗師指點] 權限檢查：必須是教職員 (Staff)
            // 原本僅限 Admin，現放寬給所有老師使用
            // $canGroupCancel = $staff->staff_role === \App\Enums\StaffRole::Admin || $user->canBypassSchoolScope();

            if (!$staff) {
                return response()->json([
                    'status' => 'error',
                    'message' => '找不到您的教職員資料，無法執行操作',
                ], 403);
            }

            // 呼叫 Service 層方法
            $count = $this->mealService->createGroupCancellation(
                studentIds: $request->student_ids,
                startDate: $request->start_date,
                endDate: $request->end_date,
                requestedBy: $user,
                reason: $request->reason
            );

            // [效能優化] 不再回傳詳細名單
            return response()->json([
                'status' => 'success',
                'message' => '跨班團體停餐申請成功',
                'count' => $count,
                // 'affected_students' => $students, // 已移除
            ]);

        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Group Cancellation Error', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'request' => $request->all(),
                'user_id' => $user->id,
            ]);

            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * 搜尋學生（用於多人停餐選擇）
     */
    public function searchStudents(Request $request)
    {
        $user = Auth::user();
        $keyword = $request->input('keyword');
        $classroomId = $request->input('classroom_id'); // 新增班級篩選

        // 檢查參數：關鍵字或班級ID擇一
        if ((empty($keyword) || strlen($keyword) < 2) && empty($classroomId)) {
            return response()->json([
                'success' => true,
                'data' => [],
            ]);
        }

        // 基礎查詢：只查詢該校的活躍學生
        $query = Student::where('school_id', $user->school_id)
            ->active()
            ->with('classroom'); // 修正：移除 :id,name，因為 Classroom 沒有 name 欄位

        // ... (permission checks omit for brevity) ...

        // 篩選邏輯
        if ($classroomId) {
            // 模式 A: 依班級篩選
            $query->where('classroom_id', $classroomId);
            if (!empty($keyword)) {
                $query->where('name', 'like', "%{$keyword}%");
            }
        } else {
            // 模式 B: 全域關鍵字搜尋
            $query->where(function ($q) use ($keyword) {
                $q->where('name', 'like', "%{$keyword}%")
                    ->orWhereHas('classroom', function ($classQuery) use ($keyword) {
                        // 修正：Classroom 沒有 name 欄位，改搜尋 class_name
                        $classQuery->where('class_name', 'like', "%{$keyword}%");
                    });
            });
        }

        // 限制結果數量
        $students = $query->limit(50)->get(); // 稍微放寬數量限制以容納整班學生

        return response()->json([
            'success' => true,
            'data' => $students->map(function ($student) {
                return [
                    'id' => $student->id,
                    'name' => $student->name,
                    'class_name' => $student->classroom?->full_name ?? '未分班', // 修正：使用 full_name 屬性
                    'seat_number' => $student->seat_number,
                ];
            }),
        ]);
    }

    /**
     * 取得未來假日清單 (供前端禁用日期)
     * GET /api/cancellation/holidays
     */
    public function getHolidays(Request $request)
    {
        $user = $request->user();

        // 預設查詢未來 60 天 (涵蓋大多數預約範圍)
        $startDate = now()->startOfDay();
        $endDate = now()->addDays(60)->endOfDay();

        $holidays = [];

        // 1. 國定假日 (NationalHoliday)
        $nationalHolidays = NationalHoliday::query()
            ->whereBetween('date', [$startDate, $endDate])
            ->get();

        foreach ($nationalHolidays as $h) {
            /** @var NationalHoliday $h */
            // 補班日 (Type=2) 是要上課的，不能算在這個「禁用清單」中
            if ($h->type !== NationalHoliday::TYPE_MAKEUP) {
                /** @var \Carbon\Carbon $date */
                $date = $h->date;
                $holidays[$date->format('Y-m-d')] = $h->name;
            }
        }

        // 2. 校園例外 (SemesterException)
        if ($user->school_id) {
            $exceptions = SemesterException::query()
                ->where('date', '>=', $startDate)
                ->where('date', '<=', $endDate)
                ->whereHas('semester', function ($q) use ($user) {
                    $q->where('school_id', $user->school_id);
                })
                ->get();

            foreach ($exceptions as $ex) {
                /** @var SemesterException $ex */
                // is_meal_day = false 代表不用餐
                if (!$ex->is_meal_day) {
                    /** @var \Carbon\Carbon $date */
                    $date = $ex->date;
                    $holidays[$date->format('Y-m-d')] = $ex->description ?? '校園活動';
                }
            }
        }

        return response()->json([
            'status' => 'success',
            'holidays' => $holidays,
        ]);
    }
}
