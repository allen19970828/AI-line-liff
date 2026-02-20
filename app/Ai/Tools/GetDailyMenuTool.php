<?php

namespace App\Ai\Tools;

use Carbon\Carbon;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;
use Stringable;

class GetDailyMenuTool implements Tool
{
    public function name(): string
    {
        return 'get_daily_menu';
    }

    /**
     * Get the description of the tool's purpose.
     */
    public function description(): Stringable|string
    {
        return '查詢指定日期的午餐菜單（對接校園食材登錄平台）。';
    }

    /**
     * Execute the tool.
     */
    public function handle(Request $request): Stringable|string
    {
        $date = $request->string('date') ?? Carbon::today()->toDateString();
        $user = auth()->user();

        if (! $user) {
            return '錯誤：無法識別您的身份。請先登入後再查詢。';
        }

        $school = $user->school;
        if (! $school || ! $school->code) {
            return '錯誤：您的所屬學校尚未設定食材登錄代碼 (MOE Code)，無法生成查詢連結。';
        }

        $schoolCode = $school->code;
        // 確保日期格式為 YYYY-MM-DD
        try {
            $formattedDate = Carbon::parse($date)->toDateString();
        } catch (\Exception $e) {
            $formattedDate = Carbon::today()->toDateString();
        }

        /** @var \App\Services\MealService $mealService */
        $mealService = app(\App\Services\MealService::class);
        $menuData = $mealService->getMenuFromFatrace($schoolCode, $formattedDate);

        if (! $menuData) {
            $url = "https://fatraceschool.k12ea.gov.tw/frontend/search.html?school={$schoolCode}&period={$formattedDate}";

            return "🍴 **{$school->name}** {$formattedDate} 目前在食登平台上抓不到資料（可能是尚未上傳或本日不供餐）。\n\n您可以自行點擊連結確認：\n[👉 點此查看外部連結過往資料]({$url})";
        }

        // 組裝 Flex Message JSON
        // 我們使用一個特殊的前綴 FLEX: 讓 LineBotController 識別
        $flexData = [
            'type' => 'bubble',
            'header' => [
                'type' => 'box',
                'layout' => 'vertical',
                'contents' => [
                    [
                        'type' => 'text',
                        'text' => '🏫 '.$menuData['school_name'],
                        'weight' => 'bold',
                        'size' => 'sm',
                        'color' => '#1DB446',
                    ],
                    [
                        'type' => 'text',
                        'text' => $formattedDate.' 午餐菜單',
                        'weight' => 'bold',
                        'size' => 'xl',
                        'margin' => 'md',
                    ],
                ],
            ],
            'body' => [
                'type' => 'box',
                'layout' => 'vertical',
                'contents' => [],
            ],
            'footer' => [
                'type' => 'box',
                'layout' => 'vertical',
                'spacing' => 'sm',
                'contents' => [
                    [
                        'type' => 'button',
                        'style' => 'link',
                        'height' => 'sm',
                        'action' => [
                            'type' => 'uri',
                            'label' => '🍽️ 查看詳細食材資訊',
                            'uri' => $menuData['source_url'],
                        ],
                    ],
                ],
                'flex' => 0,
            ],
        ];

        // 填充菜色
        foreach ($menuData['dishes'] as $dish) {
            $dishType = $dish['DishType'] ?? '其他';
            $dishName = $dish['DishName'] ?? '未提供名稱';

            if (in_array($dishType, ['附餐', '調味料'])) {
                continue;
            }
            $flexData['body']['contents'][] = [
                'type' => 'box',
                'layout' => 'horizontal',
                'margin' => 'md',
                'contents' => [
                    [
                        'type' => 'text',
                        'text' => $dishType,
                        'size' => 'sm',
                        'color' => '#aaaaaa',
                        'flex' => 1,
                    ],
                    [
                        'type' => 'text',
                        'text' => $dishName,
                        'size' => 'sm',
                        'color' => '#666666',
                        'flex' => 4,
                        'wrap' => true,
                    ],
                ],
            ];
        }

        // 加入熱量
        if ($menuData['calorie']) {
            $flexData['body']['contents'][] = [
                'type' => 'separator',
                'margin' => 'xxl',
            ];
            $flexData['body']['contents'][] = [
                'type' => 'box',
                'layout' => 'horizontal',
                'margin' => 'md',
                'contents' => [
                    [
                        'type' => 'text',
                        'text' => '🔥 熱量',
                        'size' => 'sm',
                        'color' => '#aaaaaa',
                        'flex' => 1,
                    ],
                    [
                        'type' => 'text',
                        'text' => $menuData['calorie'].' kcal',
                        'size' => 'sm',
                        'color' => '#666666',
                        'flex' => 4,
                    ],
                ],
            ];
        }

        return 'FLEX:'.json_encode($flexData);
    }

    /**
     * Get the tool's schema definition.
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'date' => $schema->string()->description('查詢日期 (YYYY-MM-DD)，請推算至具體日期。')->required(),
        ];
    }
}
