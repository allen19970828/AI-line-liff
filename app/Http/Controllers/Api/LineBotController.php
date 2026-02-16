<?php

namespace App\Http\Controllers\Api;

use App\Ai\Agents\LunchAgent;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use LINE\Clients\MessagingApi\Model\ReplyMessageRequest;
use LINE\Clients\MessagingApi\Model\TextMessage;
use LINE\Constants\HTTPHeader;
use LINE\Parser\EventRequestParser;
use LINE\Webhook\Model\MessageEvent;
use LINE\Webhook\Model\TextMessageContent;

class LineBotController extends Controller
{
    public function handle(Request $request)
    {
        // 1. 準備金鑰
        $channelSecret = config('services.line.channel_secret');
        $channelToken = config('services.line.channel_access_token');

        // 2. 取得 LINE 的簽章 (X-Line-Signature)
        $signature = $request->header(HTTPHeader::LINE_SIGNATURE);

        if (empty($signature)) {
            return response('Bad Request', 400);
        }

        // 3. 驗證簽章與解析事件
        // 這是 SDK 幫我們做的最重要的一步：確認訊息真的來自 LINE
        try {
            $parsedEvents = EventRequestParser::parseEventRequest(
                $request->getContent(),
                $channelSecret,
                $signature
            );
        } catch (\Exception $e) {
            Log::error('LINE Signature Error: '.$e->getMessage());

            return response('Invalid Signature', 400);
        }

        // 4. 初始化 API 客戶端 (用來回話)
        $client = new \LINE\Clients\MessagingApi\Api\MessagingApiApi(
            new \GuzzleHttp\Client,
            new \LINE\Clients\MessagingApi\Configuration,
        );
        $client->getConfig()->setAccessToken($channelToken);

        // 5. 處理所有事件 (因為 LINE 可能一次傳多個)
        foreach ($parsedEvents->getEvents() as $event) {

            // 我們目前只處理「文字訊息」
            if (! ($event instanceof MessageEvent)) {
                continue;
            }
            $messageContent = $event->getMessage();
            if (! ($messageContent instanceof TextMessageContent)) {
                continue;
            }

            $userMessage = $messageContent->getText();
            $replyToken = $event->getReplyToken();
            $lineUserId = $event->getSource()?->getUserId();

            // ==========================================
            // 🔐 宗師邏輯區：建立 LINE 身分連結
            // ==========================================

            // 1. 嘗試透過 LINE ID 找綁定帳號
            $lineAccount = \App\Models\LineAccount::withoutGlobalScopes()
                ->where('line_user_id', $lineUserId)
                ->first();

            $user = $lineAccount?->user;

            if (! $user) {
                // 如果還沒綁定，雖然我們還是可以回話，但 AI 那邊如果用到 auth()->user() 會炸開
                // 這裡可以做一個簡單的指令攔截，或是讓 AI 自己處理 (但 Tool 會報錯)
                // 這裡我們先做一個簡單的綁定引導:
                if (trim($userMessage) === '綁定' || str_contains($userMessage, '綁定')) {
                    // [TODO] 這裡應該接上正式的綁定流程 (例如回傳一個連結)
                    // 暫時回應：
                    $replyText = '請登入系統後，在個人設定中連結 LINE 帳號。🔗';
                    $this->replyText($client, $replyToken, $replyText);

                    continue;
                }
            } else {
                // 2. 👑 關鍵：讓 Laravel 記得這個人 (手動登入)
                auth()->login($user);
            }

            // ==========================================
            // 🧠 宗師邏輯區：官方 SDK 版本
            // ==========================================

            // 預設回覆，萬一 AI 死掉還有這句話
            $replyText = '小秘正在維修中...🔧';

            try {
                // 呼叫代理人，一句話搞定！
                $response = (new LunchAgent)->prompt($userMessage);

                $replyText = (string) $response;

            } catch (\Throwable $e) { // 改用 Throwable 抓取所有嚴重錯誤
                Log::error('Groq 嚴重錯誤: '.$e->getMessage());
                Log::error($e->getTraceAsString()); // 記錄詳細錯誤位置
                $replyText = '小秘發生錯誤：'.$e->getMessage(); // 把錯誤直接回傳到 LINE (開發階段用)
            }
            // 6. 回覆訊息
            try {
                $message = new TextMessage(['type' => 'text', 'text' => $replyText]);
                $request = new ReplyMessageRequest([
                    'replyToken' => $replyToken,
                    'messages' => [$message],
                ]);

                $client->replyMessage($request);

            } catch (\Exception $e) {
                Log::error('LINE Reply Error: '.$e->getMessage());
            }
        }

        return response('OK', 200);
    }

    private function replyText($client, $replyToken, $text)
    {
        try {
            $message = new \LINE\Clients\MessagingApi\Model\TextMessage(['type' => 'text', 'text' => $text]);
            $request = new \LINE\Clients\MessagingApi\Model\ReplyMessageRequest([
                'replyToken' => $replyToken,
                'messages' => [$message],
            ]);
            $client->replyMessage($request);
        } catch (\Exception $e) {
            Log::error('LINE Reply Error: '.$e->getMessage());
        }
    }
}
