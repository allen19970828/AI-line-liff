<?php

namespace App\Http\Controllers\Api\Liff;

use App\Http\Controllers\Controller;
use App\Models\MealCancellationGroup;
use App\Models\Student;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class MealCancellationGroupController extends Controller
{
    /**
     * 列出我的群組
     */
    public function index(Request $request)
    {
        $user = Auth::user();

        $groups = MealCancellationGroup::where('school_id', $user->school_id)
            ->with(['students:id,name,classroom_id,seat_number', 'students.classroom:id,name'])
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($group) {
                return [
                    'id' => $group->id,
                    'name' => $group->name,
                    'description' => $group->description,
                    'student_count' => $group->students->count(),
                    'students' => $group->students->map(function ($student) {
                        return [
                            'id' => $student->id,
                            'name' => $student->name,
                            'class_name' => $student->classroom?->name ?? '未分班',
                            'seat_number' => $student->seat_number,
                        ];
                    }),
                    'created_at' => $group->created_at->format('Y-m-d H:i'),
                    'can_edit' => $this->canManageGroup($user, $group),
                ];
            });

        return response()->json([
            'success' => true,
            'data' => $groups,
        ]);
    }

    /**
     * 建立群組
     */
    public function store(Request $request)
    {
        $user = Auth::user();

        $validated = $request->validate([
            'name' => 'required|string|max:100',
            'description' => 'nullable|string',
            'student_ids' => 'required|array|min:1',
            'student_ids.*' => 'exists:students,id',
        ]);

        // 檢查群組名稱是否重複
        $exists = MealCancellationGroup::where('school_id', $user->school_id)
            ->where('name', $validated['name'])
            ->exists();

        if ($exists) {
            return response()->json([
                'success' => false,
                'message' => '群組名稱已存在，請使用其他名稱',
            ], 422);
        }

        // 驗證學生是否屬於該學校
        $students = Student::whereIn('id', $validated['student_ids'])
            ->where('school_id', $user->school_id)
            ->get();

        if ($students->count() !== count($validated['student_ids'])) {
            return response()->json([
                'success' => false,
                'message' => '部分學生不存在或不屬於您的學校',
            ], 422);
        }

        // 權限檢查：一般教師僅能加入自己班級的學生（行政與午餐秘書可加入任意學生）
        if (! $this->canAddAnyStudent($user)) {
            $teacherClassrooms = $user->staff?->classrooms->pluck('id') ?? collect();
            $invalidStudents = $students->filter(function ($student) use ($teacherClassrooms) {
                return ! $teacherClassrooms->contains($student->classroom_id);
            });

            if ($invalidStudents->isNotEmpty()) {
                return response()->json([
                    'success' => false,
                    'message' => '您只能將自己班級的學生加入群組',
                ], 403);
            }
        }

        // 建立群組
        DB::beginTransaction();
        try {
            $group = MealCancellationGroup::create([
                'school_id' => $user->school_id,
                'name' => $validated['name'],
                'description' => $validated['description'] ?? null,
                'created_by' => $user->id,
            ]);

            $group->students()->attach($validated['student_ids']);

            DB::commit();

            return response()->json([
                'success' => true,
                'data' => [
                    'id' => $group->id,
                    'name' => $group->name,
                    'student_count' => count($validated['student_ids']),
                ],
                'message' => "群組「{$group->name}」已建立（{$students->count()} 位成員）",
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => '建立群組失敗：'.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * 更新群組
     */
    public function update(Request $request, MealCancellationGroup $group)
    {
        $user = Auth::user();

        // 權限檢查
        if (! $this->canManageGroup($user, $group)) {
            return response()->json([
                'success' => false,
                'message' => '您沒有權限編輯此群組',
            ], 403);
        }

        $validated = $request->validate([
            'name' => 'sometimes|string|max:100',
            'description' => 'nullable|string',
            'student_ids' => 'sometimes|array',
            'student_ids.*' => 'exists:students,id',
        ]);

        // 檢查名稱重複（排除自己）
        if (isset($validated['name'])) {
            $exists = MealCancellationGroup::where('school_id', $user->school_id)
                ->where('name', $validated['name'])
                ->where('id', '!=', $group->id)
                ->exists();

            if ($exists) {
                return response()->json([
                    'success' => false,
                    'message' => '群組名稱已存在，請使用其他名稱',
                ], 422);
            }
        }

        DB::beginTransaction();
        try {
            if (isset($validated['name']) || isset($validated['description'])) {
                $group->update($validated);
            }

            if (isset($validated['student_ids'])) {
                // 驗證學生權限
                $students = Student::whereIn('id', $validated['student_ids'])
                    ->where('school_id', $user->school_id)
                    ->get();

                if (! $this->canAddAnyStudent($user)) {
                    $teacherClassrooms = $user->staff?->classrooms->pluck('id') ?? collect();
                    $invalidStudents = $students->filter(function ($student) use ($teacherClassrooms) {
                        return ! $teacherClassrooms->contains($student->classroom_id);
                    });

                    if ($invalidStudents->isNotEmpty()) {
                        DB::rollBack();

                        return response()->json([
                            'success' => false,
                            'message' => '您只能將自己班級的學生加入群組',
                        ], 403);
                    }
                }

                $group->students()->sync($validated['student_ids']);
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'data' => [
                    'id' => $group->id,
                    'name' => $group->name,
                    'student_count' => $group->students()->count(),
                ],
                'message' => "群組「{$group->name}」已更新",
            ]);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => '更新群組失敗：'.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * 刪除群組
     */
    public function destroy(MealCancellationGroup $group)
    {
        $user = Auth::user();

        // 權限檢查
        if (! $this->canManageGroup($user, $group)) {
            return response()->json([
                'success' => false,
                'message' => '您沒有權限刪除此群組',
            ], 403);
        }

        $groupName = $group->name;
        $group->delete();

        return response()->json([
            'success' => true,
            'message' => "群組「{$groupName}」已刪除",
        ]);
    }

    /**
     * 權限檢查：是否可管理群組
     */
    private function canManageGroup($user, $group): bool
    {
        // 午餐秘書與行政可編輯所有群組
        if ($user->canBypassSchoolScope() || $user->staff?->staff_role?->value === 'admin') {
            return true;
        }

        // 一般教師僅能編輯自己建立的群組
        return $group->created_by === $user->id;
    }

    /**
     * 權限檢查：是否可新增任意學生
     */
    private function canAddAnyStudent($user): bool
    {
        return $user->canBypassSchoolScope() || $user->staff?->staff_role?->value === 'admin';
    }
}
