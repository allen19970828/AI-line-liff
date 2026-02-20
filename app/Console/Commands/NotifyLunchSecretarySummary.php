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
        // 1. 取得目標日期在該校的所有已核准停餐申請
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

        $reportMessage .= "\n\n(系統自動發送，無需回覆)";

        $this->line($reportMessage);

        // 2. 寫入 Log
        \Illuminate\Support\Facades\Log::channel('daily')->info($reportMessage);

        // 3. 宗師絕技：找出該校的午餐秘書並發送 LINE
        // 假設角色名稱為 'lunch_secretary' (需確認資料庫中的實際 Role Name，這裡先用通用猜測)
        // 且必須已經綁定 LINE 帳號
        $secretaries = \App\Models\User::role('lunch_secretary')
            ->where('school_id', $semester->school_id)
            ->whereHas('lineAccount')
            ->get();

        if ($secretaries->isEmpty()) {
            $this->warn("學校 [{$semester->school->name}] 無綁定 LINE 的午餐秘書，無法推播。");

            return;
        }

        // 初始化 LINE Client
        $httpClient = new \GuzzleHttp\Client;
        $config = new \LINE\Clients\MessagingApi\Configuration;
        $config->setAccessToken(config('services.line.channel_access_token'));
        $messagingApi = new \LINE\Clients\MessagingApi\Api\MessagingApiApi($httpClient, $config);

        foreach ($secretaries as $secretary) {
            try {
                $textMessage = new \LINE\Clients\MessagingApi\Model\TextMessage([
                    'type' => 'text',
                    'text' => $reportMessage,
                ]);

                $pushRequest = new \LINE\Clients\MessagingApi\Model\PushMessageRequest([
                    'to' => $secretary->lineAccount->line_user_id,
                    'messages' => [$textMessage],
                ]);

                $messagingApi->pushMessage($pushRequest);
                $this->info("已推播給秘書：{$secretary->name}");

            } catch (\Exception $e) {
                $this->error("推播失敗 [{$secretary->name}]: ".$e->getMessage());
            }
        }
    }
}
