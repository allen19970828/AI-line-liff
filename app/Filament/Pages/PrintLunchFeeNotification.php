<?php

namespace App\Filament\Pages;

use App\Enums\GradeLevel;
use App\Models\Semester;
use App\Services\MealBillingService;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Auth;

class PrintLunchFeeNotification extends Page
{
    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-printer';

    protected static ?string $navigationLabel = '午餐費通知單列印';

    protected static string|\UnitEnum|null $navigationGroup = '報表管理';

    protected static ?string $title = '午餐費通知單列印';

    protected string $view = 'filament.pages.print-lunch-fee-notification';

    public ?array $data = [];

    public function mount(): void
    {
        $school = Auth::user()->school;
        $activeSemester = Semester::where('school_id', $school?->id)
            ->where('is_active', true)
            ->first();

        // 預設內容 (參考圖中樣式)
        $this->form->fill([
            'semester_id' => $activeSemester?->id,
            'school_name' => $school?->name ?? '○○國小',
            'academic_year' => '114',
            'term' => '一',
            'header' => '親愛的家長您好：',
            'intro' => '自本學年度起午餐費改為 2 次收費，第一期為 9-11 月、第二期為 12-1 月，煩請家長留意，以下詳列出本學期用餐日期供參考，感謝家長們大力支持營養午餐業務，一起關心孩子吃的健康。',
            'footer' => "★每餐收費：學生自費 48 元（政府另外補助 5 元），共計 53 元\n★若有退餐費用，學期結束後有郵局帳戶學生會直接退至帳戶；無郵局帳戶學生會以現金退費",
        ]);
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Select::make('semester_id')
                    ->label('選擇計算基準學期')
                    ->options(Semester::all()->pluck('name', 'id'))
                    ->required()
                    ->live(),
                TextInput::make('school_name')
                    ->label('學校名稱')
                    ->required()
                    ->live(),
                TextInput::make('academic_year')
                    ->label('學年度')
                    ->placeholder('例如: 114')
                    ->required()
                    ->live(),
                Select::make('term')
                    ->label('學期別')
                    ->options([
                        '一' => '第一學期',
                        '二' => '第二學期',
                    ])
                    ->required()
                    ->live(),
                TextInput::make('header')
                    ->label('抬頭稱呼')
                    ->live(),
                Textarea::make('intro')
                    ->label('引言內容')
                    ->rows(3)
                    ->live(),
                Textarea::make('footer')
                    ->label('頁尾備註')
                    ->rows(3)
                    ->live(),
            ])
            ->statePath('data')
            ->columns(2);
    }

    /**
     * 取得日曆資料 (用於 Blade)
     */
    public function getCalendarData(): array
    {
        if (empty($this->data['semester_id'])) {
            return [];
        }

        $semester = Semester::find($this->data['semester_id']);
        if (! $semester) {
            return [];
        }

        return app(MealBillingService::class)->generateCalendarMatrix($semester);
    }

    /**
     * 取得費用總結資料 (用於 Blade)
     */
    public function getBillingOverview(): array
    {
        if (empty($this->data['semester_id'])) {
            return [];
        }

        $semester = Semester::find($this->data['semester_id']);
        if (! $semester) {
            return [];
        }

        $service = app(MealBillingService::class);
        $breakdown = $semester->cost_breakdown ?? [];
        $studentPay = $breakdown['student_pay'] ?? 48; // 預設 48 元

        $grades = [
            GradeLevel::GRADE_1,
            GradeLevel::GRADE_2,
            GradeLevel::GRADE_3,
            GradeLevel::GRADE_4,
            GradeLevel::GRADE_5,
            GradeLevel::GRADE_6,
        ];

        $patterns = [
            'all' => [1, 2, 3, 4, 5],
            'tue_only' => [2],
            'no_wed' => [1, 2, 4, 5],
            'no_wed_fri' => [1, 2, 4],
        ];

        // 期數劃分：參考圖片為 9-11月 與 12-1月
        $start = $semester->start_date;
        $end = $semester->end_date;

        /** @var \Carbon\Carbon $start */
        // 找到第一期結束日 (11/30) 與第二期開始日 (12/01)
        $p1End = $start->copy()->month(11)->endOfMonth();
        $p2Start = $start->copy()->month(12)->startOfMonth();

        $rows = [];
        foreach ($grades as $grade) {
            $row = [
                'label' => $grade->getLabel(),
                'patterns' => [],
            ];

            foreach ($patterns as $key => $days) {
                $daysP1 = $service->calculateMealDays($semester, $start, $p1End, $days, (string) $grade->value);
                $daysP2 = $service->calculateMealDays($semester, $p2Start, $end, $days, (string) $grade->value);

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
