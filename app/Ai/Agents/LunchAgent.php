<?php

namespace App\Ai\Agents;

use Laravel\Ai\Attributes\Model;
use Laravel\Ai\Attributes\Provider;
use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Contracts\Conversational;
use Laravel\Ai\Contracts\HasTools;
use Laravel\Ai\Promptable;

#[Provider('groq')]
#[Model('llama-3.3-70b-versatile')]
class LunchAgent implements Agent, Conversational, HasTools
{
    use Promptable;

    /**
     * Get the instructions that the agent should follow.
     */
    public function instructions(): string
    {
        return "你是一位學校午餐秘書。

        【行為準則】
        1. 你的目標是協助停餐或查菜單，但**也要能正常聊天**。請使用繁體中文（Traditional Chinese, Taiwan）回答。
        2. 如果使用者說「取消」、「不要了」、「算了」，請**立刻停止**追問，並切換回輕鬆聊天的模式。
        3. 如果使用者在抱怨（如：秀斗、壞了），請以「專業且具同理心」的態度安撫，不要再機械式地要資料。
        4. 只有在使用者明確提供姓名(或班級)與日期時，才呼叫 `cancel_meal` 工具。
        5. **重要**：在呼叫 `cancel_meal` 工具時，必須將所有參數（scope, student_name 或 class_name, date, reason）封裝在名為 `schema_definition` 的物件中。

        【關鍵邏輯：停餐範圍】
        1. 當使用者提到「全班」、「全體」、「大家」或是直接說出班級名稱（如：六年忠班）時，請務必將 `scope` 設為 `class`。此時必須提供 `class_name`。
        2. 當使用者只說人名（如：王小明）時，將 `scope` 設為 `individual`。此時必須提供 `student_name`。

        【範例】
        - User:「幫王小明停餐」 -> tool: {scope: 'individual', student_name: '王小明', ...}
        - User:「六年忠班明天全體停餐」 -> tool: {scope: 'class', class_name: '六年忠班', ...}

        【時間觀念】
        現在時刻是：".now()->toDateTimeString().'。
        當使用者說「明天」、「下週一」時，請根據現在時刻推算準確的 `YYYY-MM-DD` 日期。
        ';
    }

    /**
     * Get the list of messages comprising the conversation so far.
     */
    public function messages(): iterable
    {
        return [];
    }

    /**
     * Get the tools available to the agent.
     */
    public function tools(): iterable
    {
        return [
            // new \App\Ai\Tools\CancelMealTool(app(\App\Services\MealService::class)),
        ];
    }
}
