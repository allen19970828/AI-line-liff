<?php

namespace App\Filament\Resources\Students\Schemas;

use App\Enums\DietaryType;
use App\Enums\SemesterStage;
use App\Enums\StudentIdentity;
use App\Enums\StudentStatus;
use App\Enums\SubsidyReason; // 假設你有這個
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Builder;

class StudentForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                // === 左側主戰場 (佔 2/3) ===
                Group::make()
                    ->columnSpan(['lg' => 2])
                    ->schema([
                        // [第一卷：學籍核心]
                        Section::make('學籍資料')
                            ->icon('heroicon-m-academic-cap')
                            ->schema([
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

                                Grid::make(2)->schema([
                                    Select::make('classroom_id')
                                        ->label('班級')
                                        ->relationship(
                                            'classroom',
                                            'class_name',
                                            fn (Builder $query) => $query->where('school_id', auth()->user()->school?->id)
                                        )
                                        ->searchable()
                                        ->preload()
                                        ->required(),

                                    TextInput::make('seat_number')
                                        ->label('座號')
                                        ->numeric()
                                        ->maxLength(5),
                                ]),

                                Grid::make(2)->schema([
                                    TextInput::make('name')
                                        ->label('姓名')
                                        ->required()
                                        ->maxLength(64),

                                    TextInput::make('student_code')
                                        ->label('學號')
                                        ->unique(ignoreRecord: true)
                                        ->maxLength(32),
                                ]),

                                Grid::make(2)->schema([
                                    Select::make('stage')
                                        ->label('學制部別')
                                        ->options(SemesterStage::class)
                                        ->default(SemesterStage::Elementary)
                                        ->required()
                                        ->live(),

                                    Select::make('status')
                                        ->label('在學狀態')
                                        ->options(StudentStatus::class)
                                        ->default(StudentStatus::Active)
                                        ->required(),
                                ]),
                            ]),

                        // [第二卷：補助與身分]
                        Section::make('身分與補助')
                            ->description('變更此處將影響當前計費身分')
                            ->icon('heroicon-m-currency-dollar')
                            ->schema([
                                Grid::make(2)->schema([
                                    Select::make('identity')
                                        ->label('身分別')
                                        ->options(StudentIdentity::class)
                                        ->default(StudentIdentity::SELF_PAY)
                                        ->required()
                                        ->live(),

                                    Select::make('subsidy_reason')
                                        ->label('補助原因')
                                        ->options(SubsidyReason::class)
                                        ->visible(fn (Get $get) => $get('identity') != StudentIdentity::SELF_PAY->value),
                                ]),

                                FileUpload::make('subsidy_document')
                                    ->label('證明文件')
                                    ->directory('subsidy-docs')
                                    ->visible(fn (Get $get) => $get('identity') != StudentIdentity::SELF_PAY->value),

                                Select::make('payment_method')
                                    ->label('退費方式')
                                    ->options([
                                        'bank' => '銀行轉帳',
                                        'cash' => '現金退款',
                                        'deduct' => '抵扣下期',
                                    ])
                                    ->default('bank'),
                            ]),
                    ]),

                // === 右側側翼 (佔 1/3) ===
                Group::make()
                    ->columnSpan(['lg' => 1])
                    ->schema([
                        // [第三卷：膳食管理]
                        Section::make('用餐設定')
                            ->icon('heroicon-m-cake')
                            ->schema([
                                Select::make('dietary_type')
                                    ->label('飲食習慣')
                                    ->options(DietaryType::class)
                                    ->default(DietaryType::Meat)
                                    ->required(),

                                Toggle::make('is_meal_user')
                                    ->label('參加午餐')
                                    ->default(true)
                                    ->onColor('success')
                                    ->offColor('danger')
                                    ->live(),

                                // [幼兒園專區]
                                Section::make('幼兒園餐點')
                                    ->compact()
                                    ->visible(fn (Get $get) => $get('stage') === SemesterStage::Kindergarten->value)
                                    ->schema([
                                        Toggle::make('eat_morning_snack')->label('早點'),
                                        Toggle::make('eat_lunch')->label('午餐')->default(true),
                                        Toggle::make('eat_afternoon_snack')->label('午點'),
                                    ]),

                                // 特殊用餐日
                                CheckboxList::make('meal_days')
                                    ->label('特殊用餐日')
                                    ->helperText('僅特殊生需設定，未勾選代表全吃')
                                    ->options([
                                        1 => '週一',
                                        2 => '週二',
                                        3 => '週三',
                                        4 => '週四',
                                        5 => '週五',
                                    ])
                                    ->columns(3)
                                    ->visible(fn (Get $get) => $get('is_meal_user')),
                            ]),

                        // [第四卷：備註歷程]
                        Section::make('備註與歷程')
                            ->icon('heroicon-m-clock')
                            ->schema([
                                DatePicker::make('joined_at')->label('入學日期'),
                                DatePicker::make('left_at')->label('離校日期'),
                                TextInput::make('note')->label('備註事項'),
                            ]),
                    ]),
            ])
            ->columns(3);
    }
}
