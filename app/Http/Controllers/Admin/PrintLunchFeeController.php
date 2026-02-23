<?php

namespace App\Http\Controllers\Admin;

use App\Enums\GradeLevel;
use App\Http\Controllers\Controller;
use App\Models\Semester;
use App\Services\MealBillingService;
use Illuminate\Http\Request;

class PrintLunchFeeController extends Controller
{
    public function __invoke(Request $request)
    {
        $data = $request->input('data');

        if (is_string($data)) {
            $data = json_decode($data, true);
        }

        // 確保關鍵欄位存在，避免視圖報錯
        $data['title'] = $data['title'] ?? (($data['school_name'] ?? '○○國小').'午餐費通知單');
        $data['header'] = $data['header'] ?? '親愛的家長您好：';
        $data['intro'] = $data['intro'] ?? '';
        $data['footer'] = $data['footer'] ?? '';

        if (empty($data['semester_id'])) {
            return back()->with('error', '請先選擇學期');
        }

        $semester = Semester::findOrFail($data['semester_id']);
        $service = app(MealBillingService::class);

        // 1. 取得日曆資料
        $calendar = $service->generateCalendarMatrix($semester);

        // 2. 取得費用總結資料
        $overview = $this->getBillingOverview($semester, $service, $data);

        return view('print.lunch-fee-notification', [
            'data' => $data,
            'calendar' => $calendar,
            'overview' => $overview,
        ]);
    }

    protected function getBillingOverview(Semester $semester, MealBillingService $service, array $data): array
    {
        $breakdown = $semester->cost_breakdown ?? [];
        $studentPay = $breakdown['student_pay'] ?? 48;

        $patterns = [
            'all' => [1, 2, 3, 4, 5],
            'tue_only' => [2],
            'no_wed' => [1, 2, 4, 5],
            'no_wed_fri' => [1, 2, 4],
        ];

        // 取得動態分期設定
        $periods = $data['periods'] ?? [];
        $rows = [];
        $customGrades = $data['custom_grades'] ?? [];

        foreach ($customGrades as $cg) {
            $rawGradeValue = $cg['grade_level'] ?? 1;
            $officialGrade = GradeLevel::tryFrom((int) $rawGradeValue);
            $gradeValueStr = (string) $rawGradeValue;

            $fallbackLabel = $officialGrade ? $officialGrade->getLabel() : '未命名列';
            $label = ! empty($cg['custom_label']) ? $cg['custom_label'] : $fallbackLabel;

            $row = [
                'label' => $label,
                'remark' => $cg['remark'] ?? '',
                'patterns' => [],
            ];

            foreach ($patterns as $key => $days) {
                // 計算每一期的天數與費用
                $periodResults = [];
                foreach ($periods as $p) {
                    $pStart = \Illuminate\Support\Carbon::parse($p['start_date']);
                    $pEnd = \Illuminate\Support\Carbon::parse($p['end_date']);

                    $mealDays = $service->calculateMealDays($semester, $pStart, $pEnd, $days, $gradeValueStr);

                    $periodResults[] = [
                        'label' => $p['label'] ?? '',
                        'days' => $mealDays,
                        'fee' => $mealDays * $studentPay,
                    ];
                }

                $row['patterns'][$key] = $periodResults;
            }
            $rows[] = $row;
        }

        return [
            'periods_header' => collect($periods)->pluck('label')->toArray(),
            'rows' => $rows,
        ];
    }
}
