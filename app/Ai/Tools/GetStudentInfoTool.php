<?php

namespace App\Ai\Tools;

use App\Services\StudentService;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;

class GetStudentInfoTool implements Tool
{
    protected string $name = 'get_student_info';

    protected string $description = '查詢學生基本資料（如姓名、座號、班級、目前狀態等）。僅限教職員使用。';

    public function name(): string
    {
        return $this->name;
    }

    public function description(): \Stringable|string
    {
        return $this->description;
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'name_query' => $schema->string()->description('學生姓名或部分姓名（例如：王大同）')->required(),
        ];
    }

    public function handle(Request $request): \Stringable|string
    {
        $user = auth()->user();

        // 1. 權限檢查：僅限教職員
        if (! $user || ! $user->staff) {
            return '抱歉，學生資訊查詢功能僅開放給校內教職員使用。請確保您已完成教職員身份綁定。';
        }

        $query = $request->string('name_query');
        $studentService = app(StudentService::class);

        // 2. 搜尋學生
        $students = $studentService->searchStudents($user->school_id, $query);

        if ($students->isEmpty()) {
            return "查無與「{$query}」相關的學生資料。請檢查姓名是否正確。";
        }

        if ($students->count() > 10) {
            return "符合「{$query}」的學生過多（共 {$students->count()} 位），請提供更完整的姓名進行查詢。";
        }

        // 3. 格式化回傳
        $output = "【學生資訊查詢結果】\n\n";
        foreach ($students as $student) {
            $output .= '👤 姓名：'.strip_tags($student->name)."\n";
            $output .= '🏫 班級：'.strip_tags($student->class_full_name)."\n";
            if ($student->seat_number) {
                $output .= "🔢 座號：{$student->seat_number}\n";
            }
            $output .= '🆔 學號：'.strip_tags($student->student_code ?? '')."\n";
            $output .= "📊 狀態：{$student->status->label()}\n";
            $output .= '🍱 午餐：'.($student->is_meal_user ? ($student->eat_lunch ? '用餐中' : '不訂中餐') : '非午餐戶')."\n";
            $output .= "--------------------\n";
        }

        return $output;
    }
}
