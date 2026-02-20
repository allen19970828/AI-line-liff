<?php

namespace App\Ai\Tools;

use App\Models\Classroom;
use App\Models\Student;
use App\Services\LineIdentityService;
use App\Services\MealService;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;

class CancelMealTool implements Tool
{
    public function name(): string
    {
        return 'cancel_meal';
    }

    public function description(): \Stringable|string
    {
        return '申請停餐服務。當使用者想要請假、取消用餐時使用。支援個人停餐與全班停餐。';
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'scope' => $schema->string()->description('停餐範圍：individual (個人) 或 class (全班)')->required(),
            'date' => $schema->string()->description('停餐日期 (YYYY-MM-DD)')->required(),
            'student_name' => $schema->string()->description('學生姓名 (當 scope=individual 時必填)'),
            'class_name' => $schema->string()->description('班級名稱 (當 scope=class 時必填，如：六年忠班)'),
            'reason' => $schema->string()->description('停餐原因 (選填)'),
        ];
    }

    public function handle(Request $request): \Stringable|string
    {
        $scope = $request->string('scope');
        $date = $request->string('date');
        $studentName = $request->string('student_name');
        $className = $request->string('class_name');
        $reason = $request->string('reason');

        // 1. 身份識別
        $lineIdentityService = app(LineIdentityService::class);
        $user = auth()->user(); // 假設已透過 LineBotController 登入

        if (! $user) {
            return '錯誤：無法識別您的身份。請先在系統中完成 LINE 帳號綁定。';
        }

        // 2. 處理全班停餐
        if ($scope === 'class') {
            if (! $className) {
                return '錯誤：全班停餐必須提供班級名稱。';
            }

            // 尋找班級
            $classroom = Classroom::where('name', 'like', "%{$className}%")->first();
            if (! $classroom) {
                return "錯誤：找不到名稱包含「{$className}」的班級。";
            }

            // 權限檢查：必須是該班導師 (或行政/秘書)
            // 這裡簡化檢查：如果 User 是 Staff 且是該班導師
            if ($user->isStaff()) {
                $staff = $user->staff;
                $isHomeroom = $staff->classrooms->contains($classroom->id);
                // 允許行政或導師
                if (! $isHomeroom && ! $user->canBypassSchoolScope()) {
                    return "錯誤：您不是 {$classroom->class_full_name} 的導師，無法幫全班停餐。";
                }
            } else {
                return '錯誤：只有老師可以使用全班停餐功能。';
            }

            try {
                $mealService = app(MealService::class);
                $userId = $user->id; // 記錄操作者

                // 呼叫 Service (需確認 MealService 是否支援 User 作為 requestedBy，目前簽章是 User)
                $count = $mealService->createClassActivityCancellation(
                    $classroom,
                    $date,
                    $date, // 單日
                    $user,
                    $reason ?? '班級活動'
                );

                return "成功：已為 {$classroom->class_full_name} 申請 {$date} 全班停餐 (共 {$count} 人)。";

            } catch (\Exception $e) {
                return '申請失敗：'.$e->getMessage();
            }
        }

        // 3. 處理個人停餐
        if ($scope === 'individual') {
            if (! $studentName) {
                return '錯誤：個人停餐必須提供學生姓名。';
            }

            // 尋找學生 (根據 User 權限縮小範圍)
            $students = Student::query()
                ->where('name', $studentName)
                ->when($user->isStaff() && ! $user->canBypassSchoolScope(), function ($q) use ($user) {
                    // 老師只能查自己班級
                    $classroomIds = $user->staff->classrooms->pluck('id');
                    $q->whereIn('classroom_id', $classroomIds);
                })
                // 如果是家長 (Guardians) 下次再擴充，目前假設主要是老師操作
                ->get();

            if ($students->isEmpty()) {
                return "錯誤：找不到名為「{$studentName}」的學生，或您無權操作。";
            }

            if ($students->count() > 1) {
                // 同名學生處理：回傳更多資訊讓 User 確認
                $names = $students->map(function ($s) {
                    return "{$s->classroom->class_full_name} 的 {$s->name} ({$s->seat_number}號)";
                })->implode('、');

                return "錯誤：找到多位同名學生：{$names}。請提供更詳細資訊（如班級）。";
            }

            $student = $students->first();

            try {
                $mealService = app(MealService::class);

                // 這裡我們需要一個單筆停餐的方法。
                // 暫時使用 createGroupCancellation 傳入單一 ID
                $count = $mealService->createGroupCancellation(
                    [$student->id],
                    $date,
                    $date,
                    $user,
                    $reason ?? '個人請假'
                );

                return "成功：已為 {$student->classroom->class_full_name} {$student->name} 申請 {$date} 停餐。";

            } catch (\Exception $e) {
                return '申請失敗：'.$e->getMessage();
            }
        }

        return '錯誤：未知的停餐範圍 (scope)。';
    }
}
