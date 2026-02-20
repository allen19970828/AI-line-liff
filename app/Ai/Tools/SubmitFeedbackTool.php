<?php

namespace App\Ai\Tools;

use App\Models\MealComplaint;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;

class SubmitFeedbackTool implements Tool
{
    protected string $name = 'submit_feedback';

    protected string $description = '提交午餐意見反應或滿意度評分；當使用者想抱怨餐點品質、或是讚美今天的菜色時使用。';

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
            'content' => $schema->string()->description('使用者具體的意見內容或心得')->required(),
            'score' => $schema->integer()->description('滿意度評分 (1-5)，若使用者未明確說出分數，請根據語氣自行判斷 (正面=5, 負面=1~3)'),
        ];
    }

    public function handle(Request $request): \Stringable|string
    {
        $user = auth()->user();

        // 1. 權限檢查：僅限教職員
        if (! $user || ! $user->staff) {
            return '抱歉，意見反應功能目前僅開放給教職員使用。';
        }

        $content = $request->string('content');
        $score = $request->integer('score');

        // 2. 建立回饋紀錄
        try {
            MealComplaint::create([
                'school_id' => $user->school_id,
                'classroom_id' => $user->staff->classroom_id, // 若有帶班則自動連結
                'staff_id' => $user->staff->id,
                'occurred_at' => now()->toDateString(),
                'content' => $content,
                'rating' => $score,
                'status' => 'pending',
            ]);

            $feedbackMsg = $score ? " (評分: {$score} 星)" : '';

            return "✅ 已收到您的寶貴意見{$feedbackMsg}！\n我們會轉達給廚房與行政團隊，謝謝您的回饋。";

        } catch (\Exception $e) {
            return "❌ 意見提交失敗：{$e->getMessage()}";
        }
    }
}
