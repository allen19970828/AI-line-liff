<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class NotifyLunchSecretarySummary extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:notify-lunch-secretary-summary';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '每日 16:01 彙整停餐人數給午餐秘書';

    /**
     * Execute the console command.
     */
    public function handle(): void
    {
        $tomorrow = now()->addDay()->toDateString();
        $today = now()->toDateString();

        // 取得所有目前運作中的學期
        $semesters = \App\Models\Semester::with('school')->active()->current()->get();

        if ($semesters->isEmpty()) {
            $this->info('目前無運作中的學期。');

            return;
        }

        foreach ($semesters as $semester) {
            /** @var \App\Models\Semester $semester */
            $this->info("=== 學校: {$semester->school->name} ({$semester->name}) ===");
            $this->generateReport($semester, $tomorrow);
        }
    }

    protected function generateReport(\App\Models\Semester $semester, string $date): void
    {
        // 取得目標日期在該校的所有已核准停餐申請
        $requests = \App\Models\MealCancelRequest::query()
            ->whereHasMorph('cancellable', [\App\Models\Student::class, \App\Models\Staff::class], function ($query) use ($semester) {
                $query->where('school_id', $semester->school_id);
            })
            ->where('start_date', '<=', $date)
            ->where('end_date', '>=', $date)
            ->where('status', \App\Enums\Meals\CancelRequestStatus::Approved)
            ->get();

        $total = $requests->count();
        $reportMessage = "【午餐停餐每日彙整】\n日期：{$date}\n總計停餐人數：{$total} 人\n\n";

        if ($total === 0) {
            $reportMessage .= '本日無人停餐。';
        } else {
            // 分班級統計
            $grouped = $requests->groupBy(function ($req) {
                if ($req->cancellable_type === \App\Models\Student::class) {
                    return $req->cancellable->classroom->class_full_name ?? '其它(學生)';
                }

                return '教職員';
            });

            foreach ($grouped as $className => $items) {
                $reportMessage .= " - {$className}: {$items->count()} 人\n";
            }
        }

        $this->line($reportMessage);

        // 宗師指點：將報表寫入系統日誌，方便午餐秘書查看 (或未來接軌 LINE Notify)
        \Illuminate\Support\Facades\Log::channel('daily')->info($reportMessage);
    }
}
