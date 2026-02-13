<?php

namespace App\Filament\Resources\Semesters\Schemas;

use App\Enums\SemesterStage;
use App\Enums\StaffMealMode;
use App\Models\Semester;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\KeyValue;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Illuminate\Validation\Rules\Unique;

class SemesterForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                // === 左側主戰場 (佔 2/3) ===
                Group::make()
                    ->schema([
                        // [第一卷：身分識別]
                        Section::make('學期識別')
                            ->icon('heroicon-m-identification')
                            ->schema([
                                Grid::make(2)->schema([
                                    Select::make('stage')
                                        ->label('學制/部別')
                                        ->options(SemesterStage::class)
                                        ->required()
                                        ->native(false)
                                        ->default(SemesterStage::Elementary)
                                        ->live(), // 讓它可以即時觸發驗證邏輯更新

                                    TextInput::make('code')
                                        ->label('學期代碼')
                                        ->placeholder('ex: 114-1')
                                        ->required()
                                        ->maxLength(20)
                                        // 宗師指點：複合唯一性驗證 (School + Code + Stage)
                                        ->unique(
                                            table: Semester::class,
                                            column: 'code',
                                            modifyRuleUsing: function (Unique $rule, Get $get) {
                                                // 必須同時檢查 stage，因為不同部別可以有相同的代碼
                                                return $rule->where('stage', $get('stage'));
                                            },
                                            ignoreRecord: true
                                        ),
                                ]),

                                TextInput::make('name')
                                    ->label('學期名稱')
                                    ->required()
                                    ->maxLength(255)
                                    ->columnSpanFull(),
                            ]),

                        // [第二卷：時空結界]
                        Section::make('時間範圍')
                            ->icon('heroicon-m-clock')
                            ->schema([
                                Grid::make(2)->schema([
                                    DatePicker::make('start_date')
                                        ->label('開始日期')
                                        ->required()
                                        ->displayFormat('Y-m-d'),

                                    DatePicker::make('end_date')
                                        ->label('結束日期')
                                        ->required()
                                        ->displayFormat('Y-m-d')
                                        ->after('start_date')
                                        ->validationMessages([
                                            'after' => '結束日期必須晚於開始日期',
                                        ]),
                                ]),
                            ]),

                        // [第三卷：業務規則]
                        Section::make('規則設定')
                            ->columns(2)
                            ->schema([
                                TextInput::make('min_stop_days')
                                    ->label('學生退餐(請假)')
                                    ->numeric()->default(3)->suffix('天前')->required(),

                                TextInput::make('public_leave_days')
                                    ->label('團體公假退餐')
                                    ->numeric()->default(7)->suffix('天前')->required(),

                                TextInput::make('staff_advance_days')
                                    ->label('教職員訂餐')
                                    ->numeric()->default(1)->suffix('天前')->required(),

                                Select::make('staff_meal_mode')
                                    ->label('教職員扣款模式')
                                    ->options(StaffMealMode::class)
                                    ->default(StaffMealMode::Daily)->required(),
                            ]),
                    ])
                    ->columnSpan(['lg' => 2]),

                // === 右側側翼 (佔 1/3) ===
                Group::make()
                    ->schema([
                        Section::make('狀態與計價')
                            ->schema([
                                Toggle::make('is_active')
                                    ->label('啟用學期')
                                    ->onColor('success')
                                    ->offColor('danger'),

                                TextInput::make('meal_price')
                                    ->label('收費基準 (定價)')
                                    ->prefix('$')
                                    ->numeric()
                                    ->default(0),
                            ]),

                        Section::make('成本結構 (JSONB)')
                            ->collapsible()
                            ->schema([
                                KeyValue::make('cost_breakdown')
                                    ->label('費用細項')
                                    ->keyLabel('項目')
                                    ->valueLabel('金額')
                                    ->reorderable(),
                            ]),

                        Section::make('水果供應日')
                            ->schema([
                                CheckboxList::make('fruit_days')
                                    ->label('每週供應日')
                                    ->options([
                                        1 => '週一',
                                        2 => '週二',
                                        3 => '週三',
                                        4 => '週四',
                                        5 => '週五',
                                    ])
                                    ->columns(2)
                                    ->bulkToggleable(),
                            ]),
                    ])
                    ->columnSpan(['lg' => 1]),
            ])
            ->columns(3);
    }
}
