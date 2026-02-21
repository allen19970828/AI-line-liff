<?php

namespace App\Http\Controllers\Api;

use App\Ai\Agents\LunchAgent;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use LINE\Clients\MessagingApi\Model\ReplyMessageRequest;
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
            $source = $event->getSource();
            $lineUserId = ($source instanceof \LINE\Webhook\Model\UserSource) ? $source->getUserId() : null;

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
                /** @var \Illuminate\Contracts\Auth\StatefulGuard $guard */
                $guard = auth();
                $guard->login($user);
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

            } catch (\Throwable $e) {
                Log::error('AI Agent 嚴重錯誤', ['error' => $e->getMessage(), 'trace' => $e->getTraceAsString(), 'line_user' => $lineUserId]);
                $replyText = '小秘目前忙碌中，請稍後再試 🔧';
            }
            // 6. 回覆訊息 (包含 Quick Replies)
            try {
                // 定義 Quick Reply 按鈕
                $quickReply = new \LINE\Clients\MessagingApi\Model\QuickReply([
                    'items' => [
                        new \LINE\Clients\MessagingApi\Model\QuickReplyItem([
                            'type' => 'action',
                            'action' => new \LINE\Clients\MessagingApi\Model\MessageAction([
                                'label' => '🍱 查菜單',
                                'text' => '今天吃什麼？',
                            ]),
                        ]),
                        new \LINE\Clients\MessagingApi\Model\QuickReplyItem([
                            'type' => 'action',
                            'action' => new \LINE\Clients\MessagingApi\Model\MessageAction([
                                'label' => '📋 查停餐名單', // 最多 20 字
                                'text' => '查詢今日停餐名單',
                            ]),
                        ]),
                        new \LINE\Clients\MessagingApi\Model\QuickReplyItem([
                            'type' => 'action',
                            'action' => new \LINE\Clients\MessagingApi\Model\MessageAction([
                                'label' => '👮 查學生', // 簡短標籤
                                'text' => '查詢學生資料', // 引導 AI 詢問姓名
                            ]),
                        ]),
                        new \LINE\Clients\MessagingApi\Model\QuickReplyItem([
                            'type' => 'action',
                            'action' => new \LINE\Clients\MessagingApi\Model\MessageAction([
                                'label' => '⭐ 寫評價',
                                'text' => '我要對今天的午餐評分',
                            ]),
                        ]),
                        new \LINE\Clients\MessagingApi\Model\QuickReplyItem([
                            'type' => 'action',
                            'action' => new \LINE\Clients\MessagingApi\Model\MessageAction([
                                'label' => '🗓️ 查放假',
                                'text' => '明天有放假嗎？',
                            ]),
                        ]),
                    ],
                ]);

                if (str_starts_with($replyText, 'FLEX:')) {
                    $json = substr($replyText, 5);
                    $data = json_decode($json, true);

                    // [安全強化] 對所有 Flex Message 字串值進行隱式淨化
                    $data = $this->sanitizeFlexData($data);

                    $message = \LINE\Clients\MessagingApi\Model\Message::fromAssocArray([
                        'type' => 'flex',
                        'altText' => '您有一則菜單訊息',
                        'contents' => $data,
                        'quickReply' => $quickReply, // Flex 也可以掛 Quick Reply
                    ]);
                } else {
                    $message = new \LINE\Clients\MessagingApi\Model\TextMessage([
                        'type' => 'text',
                        'text' => $replyText,
                        'quickReply' => $quickReply, // 一般文字掛 Quick Reply
                    ]);
                }

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

    private function replyText($client, string $replyToken, string $text): void
    {
        try {
            $message = new \LINE\Clients\MessagingApi\Model\TextMessage(['type' => 'text', 'text' => $text]);
            $request = new ReplyMessageRequest([
                'replyToken' => $replyToken,
                'messages' => [$message],
            ]);
            $client->replyMessage($request);
        } catch (\Exception $e) {
            Log::error('LINE Reply Error: '.$e->getMessage());
        }
    }

    /**
     * [安全結界] 遞迴清除 Flex Message JSON 中所有字串值
     * 防止 AI 工具產生的恶意内容透過 Flex Message 注入到使用者的 LINE 畫面。
     */
    private function sanitizeFlexData(mixed $data): mixed
    {
        if (is_string($data)) {
            return strip_tags($data);
        }

        if (is_array($data)) {
            foreach ($data as $key => $value) {
                $data[$key] = $this->sanitizeFlexData($value);
            }
        }

        return $data;
    }
}
