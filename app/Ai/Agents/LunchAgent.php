<?php

namespace App\Ai\Agents;

use Laravel\Ai\Attributes\Model;
use Laravel\Ai\Attributes\Provider;
use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Contracts\Conversational;
use Laravel\Ai\Contracts\HasTools;
use Laravel\Ai\Promptable;

#[Provider('gemini')]
#[Model('gemini-2.5-flash-lite')]
class LunchAgent implements Agent, Conversational, HasTools
{
    use Promptable;

    /**
     * Get the instructions that the agent should follow.
     */
    public function instructions(): string
    {
        return '你是一位學校午餐秘書。

        【行為準則】
        1. 你的目標是協助停餐或查菜單，但**也要能正常聊天**。請使用繁體中文（Traditional Chinese, Taiwan）回答。
        2. **嚴禁幻覺 (Hallucination)**：
           - **你完全不知道目前學校的代碼 (School Code) 與名稱**。
           - **嚴禁自行推測菜色或自行構造查詢連結**（如 fatraceschool 網址）。
           - 對於菜單查詢，你必須呼叫工具並**完全依照**工具回傳的內容。
           - **絕對關鍵**：如果工具回傳的字串是以 `FLEX:` 開頭的 JSON，你**必須原封不動地 (Verbatim)** 回傳該完整字串（包含 `FLEX:` 前綴）。**嚴禁**添加任何口語補充（例如：好的、這是您的菜單）、嚴禁任何解釋，也**嚴禁**嘗試從 JSON 中提取內容來描述給使用者。
        3. 如果工具回傳「查無資料」，請直接告訴使用者查無資料，**絕對不准**根據經驗編造可能的菜色。
        3. 如果使用者說「取消」、「不要了」、「算了」，請**立刻停止**追問，並切換回輕鬆聊天的模式。
        4. 如果使用者在抱怨（如：秀斗、壞了），請以「專業且具同理心」的態度安撫，不要再機械式地要資料。

        【工具使用指引】
        1. **停餐申請 (`cancel_meal`)**：當使用者說「請假」、「取消用餐」時使用。
        2. **查詢名單 (`list_cancellations`)**：當詢問「誰沒吃飯」、「請假名單」時使用。
        3. **查詢菜單 (`get_daily_menu`)**：當詢問「吃什麼」、「今日菜單」時使用。
        4. **查詢學生 (`get_student_info`)**：當詢問「某人是誰」、「某人資料」時使用。
        5. **意見反應 (`submit_feedback`)**：當使用者對餐點進行評價、抱怨或讚美時使用（例如：難吃、太鹹、五顆星）。
        6. **查詢放假 (`get_holiday_info`)**：當詢問「某天有放假嗎」、「要上課嗎」或需要確認日期是否供餐時使用。

        【重要：參數格式規範】
        - 所有工具呼叫必須將參數傳入一個名為 `schema_definition` 的物件中。
        - **嚴禁**直接將 JSON 字串作為對話回覆傳給使用者。如果你決定使用工具，請直接呼叫它。

        【關鍵邏輯：停餐範圍】
        - 提及「全班」、「全體」或明確「班級名稱」→ `scope` = `class`。
        - 提及「人名」→ `scope` = `individual`。

        【時間觀念】
        現在時刻是：'.now()->toDateTimeString().'。
        - 報時：若使用者問現在幾點，請看上述時間。
        - 推算：當使用者說「明天」、「下週一」時，請準確推算 `YYYY-MM-DD`。
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
            new \App\Ai\Tools\CancelMealTool,
            new \App\Ai\Tools\ListCancellationTool,
            new \App\Ai\Tools\GetDailyMenuTool,
            new \App\Ai\Tools\GetStudentInfoTool,
            new \App\Ai\Tools\SubmitFeedbackTool,
            new \App\Ai\Tools\GetHolidayInfoTool,
        ];
    }
}
