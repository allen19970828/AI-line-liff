<?php

namespace App\Filament\Resources\Staff\Schemas;

use App\Enums\DietaryType;
use App\Enums\SemesterStage;
use App\Enums\StaffRole;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get as SchemaGet; // [修正]
use Filament\Schemas\Schema;
use Illuminate\Validation\Rules\Unique; // [補上]

class StaffForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                // === 左側主戰場 (佔 2/3) ===
                Group::make()
                    ->schema([
                        Section::make('人員識別與歸屬')
                            ->description('設定該員的基本資料、學校歸屬與系統帳號綁定。')
                            ->icon('heroicon-m-identification')
                            ->schema([
                                Grid::make(2)->schema([
                                    // 1. 學校選擇 (多租戶邏輯)
                                    Select::make('school_id')
                                        ->label('所屬學校')
                                        ->relationship('school', 'name')
                                        ->default(fn () => auth()->user()->school_id) // 宗師指點：直接取 school_id
                                        ->required()
                                        ->selectablePlaceholder(false)
                                        // 如果是學校管理員，通常鎖定或隱藏，這裡示範鎖定
                                        ->disabled(fn () => ! auth()->user()->canBypassSchoolScope())
                                        ->dehydrated() // 即使 disabled 也要送出資料
                                        ->columnSpanFull(), // [宗師調整] 學校佔滿一列

                                    // 2. 帳號綁定
                                    Select::make('user_id')
                                        ->label('綁定系統帳號')
                                        ->relationship('user', 'name')
                                        ->searchable()
                                        ->preload()
                                        ->helperText('若該員為廚工或無需登入者，請留空。')
                                        ->columnSpanFull(), // [宗師調整] 帳號佔滿一列
                                ]),

                                Grid::make(2)->schema([
                                    TextInput::make('name')
                                        ->label('姓名')
                                        ->required()
                                        ->maxLength(255)
                                        ->prefixIcon('heroicon-m-user'),

                                    TextInput::make('job_title')
                                        ->label('顯示職稱')
                                        ->placeholder('例：教務主任')
                                        ->maxLength(255),
                                ]),

                                // 3. 卡號防呆 (唯一性驗證)
                                TextInput::make('lunch_code')
                                    ->label('感應卡號 / 識別碼')
                                    ->prefixIcon('heroicon-m-qr-code')
                                    ->maxLength(255)
                                    ->unique(
                                        table: 'school_staff',
                                        column: 'lunch_code',
                                        ignoreRecord: true,
                                        modifyRuleUsing: function (Unique $rule, SchemaGet $get) {
                                            // 確保同一間學校內卡號不重複
                                            $schoolId = $get('school_id');

                                            return $rule->where('school_id', $schoolId);
                                        }
                                    )
                                    ->helperText('用於刷卡機或條碼掃描，同校不可重複。'),
                            ]),
                    ])
                    ->columnSpan(['lg' => 2]),

                // === 右側設定區 (佔 1/3) ===
                Group::make()
                    ->schema([
                        Section::make('職務與用餐設定')
                            ->icon('heroicon-m-cog-6-tooth')
                            ->schema([
                                // 4. 核心 Enum 設定
                                Select::make('staff_role')
                                    ->label('身分類別')
                                    ->options(StaffRole::class)
                                    ->default(StaffRole::Teacher)
                                    ->required()
                                    ->native(false),

                                Select::make('stage')
                                    ->label('所屬部別 (成本中心)')
                                    ->options(SemesterStage::class)
                                    ->default(SemesterStage::Elementary)
                                    ->required()
                                    ->native(false),

                                Select::make('dietary_type')
                                    ->label('飲食習慣')
                                    ->options(DietaryType::class)
                                    ->default(DietaryType::Meat)
                                    ->required()
                                    ->native(false),

                                // 5. 用餐開關與兼職設定
                                Toggle::make('is_meal_user')
                                    ->label('是否搭伙')
                                    ->default(true)
                                    ->onColor('success')
                                    ->offColor('danger')
                                    ->live(), // 開啟連動

                                // 宗師大招：CheckboxList 處理 JSON
                                CheckboxList::make('meal_days')
                                    ->label('固定用餐日')
                                    ->options([
                                        1 => '週一',
                                        2 => '週二',
                                        3 => '週三',
                                        4 => '週四',
                                        5 => '週五',
                                    ])
                                    ->visible(fn (SchemaGet $get) => $get('is_meal_user')) // 只有搭伙才顯示
                                    ->default([1, 2, 3, 4, 5]) // 預設全選
                                    ->columns(2) // 兩欄排列
                                    ->gridDirection('row')
                                    ->bulkToggleable(), // 允許一鍵全選/全取消

                                Toggle::make('is_active')
                                    ->label('在職狀態')
                                    ->default(true)
                                    ->onColor('success')
                                    ->offColor('gray'),
                            ]),
                    ])
                    ->columnSpan(['lg' => 1]),
            ])
            ->columns(3);
    }
}
