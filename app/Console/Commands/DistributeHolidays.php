<?php

namespace App\Console\Commands;

use App\Models\NationalHoliday;
use App\Models\Semester;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class DistributeHolidays extends Command
{
    /**
     * 指令：分發假日 (預設只補缺漏，不覆蓋學校的客製化設定)
     * --force : 強制覆蓋 (慎用)
     */
    protected $signature = 'holidays:distribute {--force : 強制覆蓋學校設定}';

    protected $description = '將國定假日「溫柔」地分發給所有進行中的學期 (保底機制)';

    public function handle()
    {
        $this->info('🚀 開始執行：國定假日自動分發 (保底機制)...');
        $startTime = microtime(true);

        // 1. 找出所有「進行中」且「啟用」的學期
        // 使用 chunk 避免記憶體爆炸 (假設你有 500 間學校)
        Semester::active()->current()->chunk(100, function ($semesters) {

            foreach ($semesters as $semester) {
                $this->processSemester($semester);
            }

        });

        $duration = round(microtime(true) - $startTime, 2);
        $this->info("🎉 分發完成！耗時 {$duration} 秒。");

        // 宗師建議：寫入 Log，方便日後查帳
        Log::info("Holidays distributed successfully in {$duration}s.");
    }

    private function processSemester(Semester $semester)
    {
        // 2. 撈出該學期時間範圍內的「總表假日」
        $masterHolidays = NationalHoliday::inRange(
            $semester->start_date,
            $semester->end_date
        )->get();

        if ($masterHolidays->isEmpty()) {
            return;
        }

        $addedCount = 0;

        foreach ($masterHolidays as $holiday) {
            // [核心心法：只補不改]

            // 檢查學校的例外表 (Exceptions) 中，這一天是否「已經有紀錄」
            // 這裡的 exists() 檢查的是日期，不管它是放假還是補課，只要有設過就不動
            $exists = $semester->exceptions()
                ->where('date', $holiday->date)
                ->exists();

            // 情況 A：學校完全沒設定這天 -> 幫他加！ (系統保底)
            if (! $exists) {
                $semester->exceptions()->create([
                    'date' => $holiday->date,
                    // 這裡做轉譯：Master 的 type 對應 Exception 的 type
                    // Master(makeup) -> Exception(1:補課)
                    // Master(holiday) -> Exception(0:放假)
                    'type' => $holiday->isMakeupDay() ? 1 : 0,
                    'description' => $holiday->name.' (系統同步)',
                ]);
                $addedCount++;
            }
            // 情況 B：學校已經有設定 -> 不動它 (尊重學校自治)
            // 除非下了 --force
            elseif ($this->option('force')) {
                $semester->exceptions()->updateOrCreate(
                    ['date' => $holiday->date],
                    [
                        'type' => $holiday->isMakeupDay() ? 1 : 0,
                        'description' => $holiday->name.' (系統強制覆蓋)',
                    ]
                );
            }
        }

        if ($addedCount > 0) {
            $this->line("  ✅ [{$semester->school->name}] ({$semester->code})：自動補入 {$addedCount} 筆。");
        }
    }
}
