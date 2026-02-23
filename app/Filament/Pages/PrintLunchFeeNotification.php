<?php

namespace App\Filament\Pages;

use App\Enums\GradeLevel;
use App\Models\Semester;
use Filament\Forms\Components\DatePicker;
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
            'header' => '親愛的家長您好：',
            'intro' => '自本學年度起午餐費改為「分期收費」，以下詳列出各期用餐日期與費用計算供參考，感謝家長們大力支持營養午餐業務，一起關心孩子吃的健康。',
            'footer' => "★每餐收費：學生自費 48 元（政府另外補助 5 元），共計 53 元\n★若有退餐費用，學期結束後有郵局帳戶學生會直接退至帳戶；無郵局帳戶學生會以現金退費",
            'periods' => $this->getDefaultPeriods($activeSemester),
            'custom_grades' => [
                ['grade_level' => '1', 'custom_label' => '一年級', 'remark' => ''],
                ['grade_level' => '2', 'custom_label' => '二年級', 'remark' => ''],
                ['grade_level' => '3', 'custom_label' => '三年級', 'remark' => ''],
                ['grade_level' => '4', 'custom_label' => '四年級', 'remark' => ''],
                ['grade_level' => '5', 'custom_label' => '五年級', 'remark' => ''],
                ['grade_level' => '6', 'custom_label' => '六年級', 'remark' => ''],
            ],
        ]);
    }

    protected function getDefaultPeriods(?Semester $semester): array
    {
        if (! $semester) {
            return [];
        }

        /** @var \Carbon\Carbon $start */
        $start = $semester->start_date;
        /** @var \Carbon\Carbon $end */
        $end = $semester->end_date;

        if (! $start || ! $end) {
            return [];
        }

        // 如果是第一學期 (約 9 月開始)
        if ($start->month >= 8) {
            $p1End = $start->copy()->month(11)->endOfMonth();
            $p2Start = $start->copy()->month(12)->startOfMonth();

            return [
                ['label' => '第一階段 (9-11月)', 'start_date' => $start->toDateString(), 'end_date' => $p1End->toDateString()],
                ['label' => '第二階段 (12-1月)', 'start_date' => $p2Start->toDateString(), 'end_date' => $end->toDateString()],
            ];
        }

        // 如果是第二學期 (約 2 月開始)
        $p1End = $start->copy()->month(4)->endOfMonth();
        $p2Start = $start->copy()->month(5)->startOfMonth();

        return [
            ['label' => '第一階段 (2-4月)', 'start_date' => $start->toDateString(), 'end_date' => $p1End->toDateString()],
            ['label' => '第二階段 (5-6月)', 'start_date' => $p2Start->toDateString(), 'end_date' => $end->toDateString()],
        ];
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
                Repeater::make('periods')
                    ->label('收費分期設定')
                    ->schema([
                        TextInput::make('label')
                            ->label('分期顯示名稱')
                            ->placeholder('例如: 第一期 (9-11月)')
                            ->required()
                            ->columnSpan(2),
                        DatePicker::make('start_date')
                            ->label('開始日期')
                            ->required()
                            ->columnSpan(1),
                        DatePicker::make('end_date')
                            ->label('結束日期')
                            ->required()
                            ->columnSpan(1),
                    ])
                    ->columns(4)
                    ->reorderable(true)
                    ->cloneable(true)
                    ->itemLabel(fn (array $state): ?string => $state['label'] ?? '未命名分期')
                    ->live()
                    ->columnSpanFull(),
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
                            ->default(1)
                            ->columnSpan(1),
                        TextInput::make('custom_label')
                            ->label('自訂顯示名稱 (可選)')
                            ->placeholder('例如: 幼兒園小班')
                            ->live()
                            ->default('')
                            ->columnSpan(1),
                        TextInput::make('remark')
                            ->label('自訂專屬備註 (可選)')
                            ->placeholder('例如: 12/4 (四)')
                            ->live()
                            ->default('')
                            ->columnSpan(1),
                    ])
                    ->live(),
            ])
            ->statePath('data')
            ->columns(2);
    }
}
