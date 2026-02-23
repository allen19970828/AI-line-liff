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

        $start = $semester->start_date;
        $end = $semester->end_date;

        // 計算分期
        $p1End = $start->copy()->month(11)->endOfMonth();
        $p2Start = $start->copy()->month(12)->startOfMonth();

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
                $daysP1 = $service->calculateMealDays($semester, $start, $p1End, $days, $gradeValueStr);
                $daysP2 = $service->calculateMealDays($semester, $p2Start, $end, $days, $gradeValueStr);

                $row['patterns'][$key] = [
                    'p1_days' => $daysP1,
                    'p1_fee' => $daysP1 * $studentPay,
                    'p2_days' => $daysP2,
                    'p2_fee' => $daysP2 * $studentPay,
                ];
            }
            $rows[] = $row;
        }

        return [
            'p1_label' => '9-11月',
            'p2_label' => '12-1月',
            'rows' => $rows,
        ];
    }
}
