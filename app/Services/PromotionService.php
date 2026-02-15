<?php

namespace App\Services;

use App\Enums\GradeLevel;
use App\Enums\SemesterStage;
use App\Enums\StudentStatus;
use App\Models\Classroom;
use App\Models\Student;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PromotionService
{
    public function performYearlyPromotion(int $schoolId)
    {
        return DB::transaction(function () use ($schoolId) {

            // ==========================================
            // 第一步：[送舊] 六年級 & 大班 -> 畢業
            // ==========================================
            $this->graduateStudents($schoolId);

            // ==========================================
            // 第二步：[分流] 處理「重新編班」的年級 (2升3, 4升5)
            // ==========================================
            // 宗師改良：加入「單班保護機制」

            $shuffleGrades = [2, 4]; // 2年級、4年級結束後通常要分班

            foreach ($shuffleGrades as $g) {
                // 1. 檢查該年級有幾個班？
                $classCount = Classroom::where('school_id', $schoolId)
                    ->where('stage', SemesterStage::Elementary)
                    ->where('grade', GradeLevel::from($g))
                    ->count();

                // 2. [例外處理] 只有一個班 (偏鄉小校)，不執行解散
                if ($classCount <= 1) {
                    Log::info("學校 ID {$schoolId} 的 {$g} 年級只有 {$classCount} 班，觸發單班保護機制，跳過解散。");

                    continue; // 跳過下面的 update，學生保留在原班級
                }

                // 3. 多於一班 -> 執行解散 (踢出班級)
                Student::where('school_id', $schoolId)
                    ->where('status', StudentStatus::Active)
                    ->whereHas('classroom', function ($q) use ($g) {
                        $q->where('stage', SemesterStage::Elementary)
                            ->where('grade', GradeLevel::from($g));
                    })
                    ->update([
                        'classroom_id' => null, // 變成浪人，等待匯入
                    ]);
            }

            // ==========================================
            // 第三步：[普升] 所有剩餘班級年級 +1
            // ==========================================
            // 不管是直升的(1,3,5)，還是剛剛受到保護的(2,4)，甚至是剛剛被掏空的空班級
            // 通通執行「班級年級升級」

            // 從 5 年級往下處理到 1 年級 (5->6, 4->5, 3->4, 2->3, 1->2)
            $allGrades = [5, 4, 3, 2, 1];

            foreach ($allGrades as $g) {
                $classrooms = Classroom::where('school_id', $schoolId)
                    ->where('stage', SemesterStage::Elementary)
                    ->where('grade', $g)
                    ->get();

                foreach ($classrooms as $class) {
                    // 1. 升級年級屬性
                    $class->grade = GradeLevel::from($g + 1);

                    // 2. [選配] 嘗試自動改班名 (僅針對單純的命名規則)
                    // 例如："一年甲班" -> "二年甲班"
                    // 如果是偏鄉學校，這一步會非常貼心，因為他們連班名都不用改了
                    $this->tryAutoUpdateClassName($class, $g);

                    $class->save();
                }
            }

            Log::info("學年升級作業完成 (School ID: {$schoolId})");
        });
    }

    private function graduateStudents(int $schoolId)
    {
        // ... (保持原本的畢業邏輯)
        Student::where('school_id', $schoolId)
            ->where('status', StudentStatus::Active)
            ->whereHas('classroom', fn ($q) => $q->where('grade', GradeLevel::Grade6))
            ->update([
                'status' => StudentStatus::Graduated,
                'classroom_id' => null,
                'left_at' => now(),
            ]);

        // 記得幼兒園也要
        // ...
    }

    /**
     * [貼心小功能] 嘗試更新班名
     */
    private function tryAutoUpdateClassName(Classroom $class, int $oldGradeVal)
    {
        // 定義中文數字對照
        $map = [
            1 => '一',
            2 => '二',
            3 => '三',
            4 => '四',
            5 => '五',
            6 => '六',
        ];

        $oldChar = $map[$oldGradeVal] ?? null;
        $newChar = $map[$oldGradeVal + 1] ?? null;

        if ($oldChar && $newChar) {
            // 如果班名是 "一年甲班"，嘗試把 "一" 換成 "二"
            // 使用 limit=1 確保只換掉最前面那個 (避免換到後面的字)
            $class->class_name = preg_replace("/$oldChar/", $newChar, $class->class_name, 1);
        }
    }
}
