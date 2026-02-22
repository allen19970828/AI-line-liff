<?php

namespace App\Ai\Tools;

use App\Models\MealCancelRequest;
use Carbon\Carbon;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;

class ListCancellationTool implements Tool
{
    protected string $name = 'list_cancellations';

    protected string $description = '查詢特定日期的停餐名單。當使用者問「今天誰停餐」、「查詢停餐名單」時使用。';

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
            'date' => $schema->string()->description('查詢日期 (YYYY-MM-DD)。若未提供，預設為今天。'),
        ];
    }

    public function handle(Request $request): \Stringable|string
    {
        $date = $request->string('date') ?? Carbon::today()->toDateString();
        $targetDate = Carbon::parse($date);

        // 0. 身份驗證守衛：確認使用者已登入且有學校歸屬
        $user = auth()->user();
        if (! $user || ! $user->school_id) {
            return '抱歉，查詢停餐名單功能僅開放給已綁定學校的教職員使用。';
        }

        // 1. 查詢該日期的停餐申請 (BelongsToSchool Trait 已自動套用學校範圍隔離)
        $requests = MealCancelRequest::query()
            ->whereDate('start_date', '<=', $targetDate)
            ->whereDate('end_date', '>=', $targetDate)
            ->where('status', '!=', \App\Enums\Meals\CancelRequestStatus::Rejected) // 排除已駁回
            ->with(['cancellable', 'classroom']) // 預載入關聯
            ->get();

        if ($requests->isEmpty()) {
            return "查詢日期 {$date} 目前沒有任何停餐紀錄。";
        }

        // 2. 整理資料 (依班級分組)
        $grouped = $requests->groupBy(function ($request) {
            if ($request->classroom) {
                return $request->classroom->class_full_name;
            }
            // 嘗試透過 cancellable 獲取班級 (針對舊資料或 Student 模型)
            if ($request->cancellable_type === \App\Models\Student::class && $request->cancellable) {
                return $request->cancellable->classroom?->class_full_name ?? '未分班';
            }

            return '教職員/其他';
        })->sortKeys();

        // 3. 建構回傳訊息
        $output = "【{$date} 停餐名單】\n(共 {$requests->count()} 筆)\n\n";

        foreach ($grouped as $className => $classRequests) {
            $output .= '🏫 '.strip_tags($className)." ({$classRequests->count()}人):\n";

            // 取得人名列表
            $names = $classRequests->map(function ($req) {
                $name = strip_tags($req->cancellable?->name ?? '未知');
                $seat = '';
                if ($req->cancellable_type === \App\Models\Student::class && $req->cancellable?->seat_number) {
                    $seat = "({$req->cancellable->seat_number}號)";
                }

                return $name.$seat;
            })->implode('、');

            $output .= "   - {$names}\n\n";
        }

        return $output;
    }
}
