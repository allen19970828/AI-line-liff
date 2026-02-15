<?php

namespace App\Services;

use App\Attributes\AsMcpTool;
use App\DTOs\StudentImportData; // [新架構]
use App\Enums\GradeLevel;
use App\Enums\SemesterStage;
use App\Enums\StudentIdentity;
use App\Models\Classroom;
use App\Models\Student;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class StudentService
{
    // 快取陣列：避免匯入 1000 筆資料時查詢 1000 次資料庫
    protected array $classroomCache = [];

    /**
     * [核心] 匯入學生 (接合 DTO)
     */
    public function importStudent(StudentImportData $dto, int $schoolId): Student
    {
        // 1. 班級解析 (優先查快取 -> 資料庫 -> Regex 自動建立)
        // DTO 給我們的是 "一年甲班" (字串)，我們需要轉成 Classroom ID
        $classroom = $this->resolveClassroom($schoolId, $dto->class_name, $dto->stage);

        // 2. 寫入學生 (使用學號為唯一 Key)
        $student = Student::withTrashed()->updateOrCreate(
            [
                'school_id' => $schoolId,
                'student_code' => $dto->student_code,
            ],
            [
                'classroom_id' => $classroom?->id,
                'stage' => $dto->stage, // 學制
                'name' => $dto->name,
                'seat_number' => $dto->seat_number,

                // 身分與狀態 (DTO 已經轉好 Enum 了)
                'identity' => $dto->identity,
                'status' => $dto->status,
                'dietary_type' => $dto->dietary_type,

                // 預設值
                'is_meal_user' => true,
                // 幼兒園自動開啟點心
                'eat_morning_snack' => $dto->stage === SemesterStage::Kindergarten,
                'eat_lunch' => true,
                'eat_afternoon_snack' => $dto->stage === SemesterStage::Kindergarten,
            ]
        );

        // 3. 如果是已刪除的學生 (回鍋生)，自動還原
        if ($student->trashed()) {
            $student->restore();
        }

        // 4. [選配] 如果匯入的資料有包含特殊身分，也可以順便寫入歷史紀錄
        // $this->recordStatusHistory($student, $dto->identity, now()->toDateString(), 'Excel 匯入');

        return $student;
    }

    /**
     * 批量更新身分 (MCP Tool / Filament Action 用)
     */
    #[AsMcpTool(name: 'batch_update_identity', description: '批量更新補助身分')]
    public function batchUpdateIdentity(Collection $students, StudentIdentity $identity, ?string $reason = null, ?string $date = null): void
    {
        DB::transaction(function () use ($students, $identity, $reason, $date) {
            $students->each(function ($student) use ($identity, $reason, $date) {
                // 1. 寫入歷史
                if ($date) {
                    $this->recordStatusHistory($student, $identity, $date, $reason);
                }

                // 2. 更新本體
                $updateData = ['identity' => $identity];

                // 只有非自費生才需要寫原因
                if ($identity !== StudentIdentity::SELF_PAY) {
                    $updateData['subsidy_reason'] = $reason;
                } else {
                    $updateData['subsidy_reason'] = null;
                }

                $student->update($updateData);
            });
        });
    }

    /**
     * 寫入身分歷史 (封裝邏輯)
     */
    public function recordStatusHistory(Student $student, StudentIdentity $identity, string $startDate, ?string $note = null): void
    {
        // 關閉上一段歷史
        $student->statusHistories()
            ->whereNull('end_date')
            ->update(['end_date' => $startDate]);

        // 開啟新歷史
        $student->statusHistories()->create([
            'school_id' => $student->school_id,
            'identity' => $identity,
            'start_date' => $startDate,
            'note' => $note,
            // 建議把當時的 subsidy_reason 也存進去，這裡省略
        ]);
    }

    // --- 內部解析邏輯 (保留你的 Regex 智慧) ---

    protected function resolveClassroom(int $schoolId, string $classNameString, SemesterStage $stage): ?Classroom
    {
        if (empty($classNameString)) {
            return null;
        }

        $cacheKey = "{$schoolId}_{$classNameString}";

        if (isset($this->classroomCache[$cacheKey])) {
            return $this->classroomCache[$cacheKey];
        }

        // 嘗試解析並建立 (或尋找)
        $classroom = $this->parseAndFindClassroom($schoolId, $classNameString, $stage);

        if ($classroom) {
            $this->classroomCache[$cacheKey] = $classroom;
        }

        return $classroom;
    }

    protected function parseAndFindClassroom(int $schoolId, string $classNameString, SemesterStage $stage): Classroom
    {
        // 1. 嘗試用 Regex 解析 "一年甲班" -> Grade 1
        $grade = GradeLevel::GRADE_1; // 預設

        if ($stage === SemesterStage::Elementary) {
            if (preg_match('/^([一二三四五六1-6])年(.+)$/u', $classNameString, $matches)) {
                $gradeMap = [
                    '一' => 1,
                    '1' => 1,
                    '二' => 2,
                    '2' => 2,
                    '三' => 3,
                    '3' => 3,
                    '四' => 4,
                    '4' => 4,
                    '五' => 5,
                    '5' => 5,
                    '六' => 6,
                    '6' => 6,
                ];
                $val = $gradeMap[$matches[1]] ?? 1;
                $grade = GradeLevel::tryFrom($val) ?? GradeLevel::GRADE_1;
                // $classNameString = trim($matches[2]); // 如果你想把 "甲班" 當作班名，但我建議保留全名或視你 ClassroomModel 邏輯
            }
        }

        // 2. 使用 firstOrCreate 防止重複建立
        // [宗師金牌] 必須把 stage 和 grade 放在第一參數（查找條件）
        // 因為資料庫的 unique constraint 是 (school_id, stage, grade, class_name)
        return Classroom::firstOrCreate(
            [
                'school_id' => $schoolId,
                'stage' => $stage,           // [關鍵修正] 加入查找條件
                'grade' => $grade,           // [關鍵修正] 加入查找條件
                'class_name' => $classNameString,
            ],
            [
                // 只有在建立新記錄時才會用到的預設值
                'sort_order' => 50,
                'is_active' => true,
            ]
        );
    }
}
