<?php

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class SendBillingNotification implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public \App\Models\LunchSettlement $settlement
    ) {}

    public function handle(): void
    {
        $staff = $this->settlement->staff;

        // 確保 Staff 有綁定 LINE 帳號
        // 透過 HasOneThrough 關聯取的 LineAccount
        $lineAccount = $staff->lineAccount;

        if (! $lineAccount) {
            // Log or handle missing line account
            return;
        }

        $lineId = $lineAccount->line_user_id;

        // 構建訊息
        $month = $this->settlement->year_month;
        $days = $this->settlement->days_count;
        $amount = $this->settlement->amount;
        $deadline = \Carbon\Carbon::parse($month)->endOfMonth()->addDays(15)->format('Y/m/d');

        $message = "【午餐費用繳費通知】\n\n";
        $message .= "{$staff->name} 老師您好，\n";
        $message .= "{$month} 月份午餐結算如下：\n";
        $message .= "------------------\n";
        $message .= "用餐天數：{$days} 天\n";
        $message .= "應繳金額：NT$ {$amount}\n";
        $message .= "------------------\n";
        $message .= "請於 {$deadline} 前完成繳費，謝謝！";

        // 發送
        try {
            $client = new \LINE\Clients\MessagingApi\Api\MessagingApiApi(
                new \GuzzleHttp\Client,
                (new \LINE\Clients\MessagingApi\Configuration)->setAccessToken(config('services.line.channel_token'))
            );

            $message = new \LINE\Clients\MessagingApi\Model\TextMessage([
                'type' => 'text',
                'text' => $message,
            ]);

            $request = new \LINE\Clients\MessagingApi\Model\PushMessageRequest([
                'to' => $lineId,
                'messages' => [$message],
            ]);

            $client->pushMessage($request);

            $this->settlement->update(['status' => 'sent']);
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Failed to send billing notification: '.$e->getMessage());
        }
    }
}
