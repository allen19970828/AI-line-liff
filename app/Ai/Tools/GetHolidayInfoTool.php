<?php

namespace App\Ai\Tools;

use App\Models\NationalHoliday;
use App\Models\SemesterException;
use Carbon\Carbon;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Support\Facades\Log;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;

class GetHolidayInfoTool implements Tool
{
    protected string $name = 'get_holiday_info';

    protected string $description = '查詢特定日期是否為放假日、補課日或學校活動日。在使用者詢問「明天有放假嗎」、「下週一要上課嗎」或 AI 在處理停餐前需確認日期屬性時使用。';

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
            'date' => $schema->string()->description('查詢日期 (格式: YYYY-MM-DD)，若使用者說明天或下週，請準確推算。')->required(),
        ];
    }

    public function handle(Request $request): \Stringable|string
    {
        try {
            $dateStr = $request->string('date');
            if (! $dateStr) {
                return '請提供正確的日期格式 (YYYY-MM-DD)。';
            }

            // 嘗試解析日期
            try {
                $date = Carbon::parse($dateStr);
            } catch (\Exception $e) {
                return "日期格式錯誤：{$dateStr}，請使用 YYYY-MM-DD 格式。";
            }

            $user = auth()->user();
            $schoolId = $user?->school_id;

            $results = [];

            // 1. 檢查國定假日 (NationalHoliday)
            /** @var NationalHoliday|null $nationalHoliday */
            $nationalHoliday = NationalHoliday::where('date', $date->toDateString())->first();
            if ($nationalHoliday) {
                $results[] = '國定性質：【'.$nationalHoliday->name.'】('.$nationalHoliday->getTypeName().')';
            }

            // 2. 檢查校園例外 (SemesterException) - 需有學校上下文
            if ($schoolId) {
                $exceptions = SemesterException::where('date', $date->toDateString())
                    ->whereHas('semester', function ($query) use ($schoolId) {
                        $query->where('school_id', $schoolId);
                    })
                    ->get();

                foreach ($exceptions as $ex) {
                    /** @var SemesterException $ex */
                    $status = $ex->is_meal_day ? '有供餐' : '不供餐';
                    $results[] = '校園安排：【'.$ex->description.'】('.$ex->getTypeName().' / '.$status.')';
                }
            }

            // 3. 基礎判斷 (週末)
            $isWeekend = $date->isWeekend();

            // 設定語系以確保輸出中文星期 (若系統未設定)
            $dayOfWeek = $date->locale('zh_TW')->dayName;

            if (empty($results)) {
                if ($isWeekend) {
                    return "📅 {$dateStr} ({$dayOfWeek}) 為一般週末放假日，校園無供餐。";
                } else {
                    return "🏫 {$dateStr} ({$dayOfWeek}) 為一般上課日，校園正常供餐。";
                }
            }

            // 組合結果
            $output = "🗓️ {$dateStr} ({$dayOfWeek}) 的作息安排如下：\n";
            $output .= implode("\n", $results);

            // 總結供餐狀況
            // 邏輯：有例外則聽例外的(is_meal_day)，沒例外則聽國定假日，都沒則看週末
            $isMealDay = true;

            if ($schoolId && isset($exceptions) && $exceptions->isNotEmpty()) {
                // 取最後一個例外作為標準
                $isMealDay = $exceptions->last()->is_meal_day;
            } elseif ($nationalHoliday) {
                $isMealDay = $nationalHoliday->type === NationalHoliday::TYPE_MAKEUP;
            } else {
                $isMealDay = ! $isWeekend;
            }

            $output .= "\n\n💡 最終判定：".($isMealDay ? '✅ 該日為供餐日' : '❌ 該日為不供餐日（或放假）');

            return $output;

        } catch (\Throwable $e) {
            Log::error('GetHolidayInfoTool Error: '.$e->getMessage(), [
                'date' => $request->string('date'),
                'user_id' => auth()->id(),
                'trace' => $e->getTraceAsString(),
            ]);

            return '系統查詢假日資訊時發生暫時性錯誤，請稍後再試。';
        }
    }
}
