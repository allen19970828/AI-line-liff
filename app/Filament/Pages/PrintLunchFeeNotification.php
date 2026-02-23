<?php

namespace App\Filament\Pages;

use App\Enums\GradeLevel;
use App\Models\Semester;
use App\Services\MealBillingService;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Pages\Page;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Auth;

class PrintLunchFeeNotification extends Page implements HasForms
{
    use InteractsWithForms;

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
            'custom_grades' => [
                ['grade_level' => '1', 'custom_label' => '一年級', 'remark' => ''],
                ['grade_level' => '2', 'custom_label' => '二年級', 'remark' => ''],
                ['grade_level' => '3', 'custom_label' => '三年級', 'remark' => ''],
                ['grade_level' => '4', 'custom_label' => '四年級', 'remark' => '12/4 (四)'],
                ['grade_level' => '5', 'custom_label' => '五年級', 'remark' => ''],
                ['grade_level' => '6', 'custom_label' => '六年級', 'remark' => '12/4.5 (四五)'],
            ],
        ]);
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
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
                Repeater::make('custom_grades')
                    ->label('年段與計算列設定')
                    ->columns(3)
                    ->columnSpanFull()
                    ->reorderable(true)
                    ->collapsible(true)
                    ->itemLabel(fn (array $state): ?string => $state['custom_label'] ?? '未命名年段')
                    ->schema([
                        Select::make('grade_level')
                            ->label('請選擇計算使用的學制年級')
                            ->options(GradeLevel::class)
                            ->required()
                            ->live()
                            ->columnSpan(1),
                        TextInput::make('custom_label')
                            ->label('自訂顯示名稱 (可選)')
                            ->placeholder('例如: 幼兒園小班')
                            ->live()
                            ->columnSpan(1),
                        TextInput::make('remark')
                            ->label('自訂專屬備註 (可選)')
                            ->placeholder('例如: 12/4 (四)')
                            ->live()
                            ->columnSpan(1),
                    ])
                    ->live(),
            ])
            ->statePath('data')
            ->columns(2);
    }

    public function preparePrint(): ?string
    {
        try {
            // 確保抓取到表單最新的狀態
            $this->data = $this->form->getState();

            if (empty($this->data['semester_id'])) {
                // 使用最新的 Filament Notifications 寫法
                \Filament\Notifications\Notification::make()
                    ->title('請先選擇學期')
                    ->warning()
                    ->send();

                return null;
            }

            $key = (string) \Illuminate\Support\Str::uuid();
            $payload = [
                'data' => $this->data,
                'calendar' => $this->getCalendarData(),
                'overview' => $this->getBillingOverview(),
            ];

            \Illuminate\Support\Facades\Cache::put('print_job_'.$key, $payload, now()->addMinutes(30));

            // 回傳帶有 UUID 的專屬列印路由 (相對路徑避免 HTTPS 代理問題)
            return route('print.lunch-fee-notification', ['key' => $key], false);
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error('PrintLunchFee Error: '.$e->getMessage(), ['trace' => $e->getTraceAsString()]);
            \Filament\Notifications\Notification::make()
                ->title('列印準備失敗')
                ->body('錯誤原因: '.$e->getMessage().' (請聯絡管理員)')
                ->danger()
                ->send();

            return null;
        }
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
            return ['rows' => []];
        }

        $semester = Semester::find($this->data['semester_id']);
        if (! $semester) {
            return ['rows' => []];
        }

        $service = app(MealBillingService::class);
        $breakdown = $semester->cost_breakdown ?? [];
        $studentPay = $breakdown['student_pay'] ?? 48; // 預設 48 元

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
        $customGrades = $this->data['custom_grades'] ?? [];

        foreach ($customGrades as $cg) {
            $rawGradeValue = $cg['grade_level'] ?? 1;

            if ($rawGradeValue instanceof GradeLevel) {
                $officialGrade = $rawGradeValue;
                $gradeValueStr = (string) $officialGrade->value;
            } else {
                $officialGrade = GradeLevel::tryFrom((int) $rawGradeValue);
                $gradeValueStr = (string) $rawGradeValue;
            }

            $fallbackLabel = $officialGrade ? $officialGrade->getLabel() : '未命名列';
            $label = ! empty($cg['custom_label']) ? $cg['custom_label'] : $fallbackLabel;

            $row = [
                'label' => $label,
                'remark' => $cg['remark'] ?? '',
                'patterns' => [],
            ];

            foreach ($patterns as $key => $days) {
                // 將計算年級的 string 型別餵入 service
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
