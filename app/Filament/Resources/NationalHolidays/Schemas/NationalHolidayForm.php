<?php

namespace App\Filament\Resources\NationalHolidays\Schemas;

use App\Models\NationalHoliday;
use Carbon\Carbon;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;

class NationalHolidayForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('假日資訊')
                    ->description('設定國定假日的日期與類型')
                    ->icon('heroicon-m-calendar-days')
                    ->schema([
                        Grid::make(2)->schema([
                            // [1. 日期]
                            DatePicker::make('date')
                                ->label('日期')
                                ->required()
                                ->native(false)
                                ->displayFormat('Y-m-d')
                                ->unique(ignoreRecord: true) // 總表日期必須唯一
                                ->live(onBlur: true) // 當日期變動後，立即觸發邏輯
                                ->afterStateUpdated(function (Get $get, Set $set, ?string $state) {
                                    // 宗師貼心功能：選了日期，自動幫忙填寫年份欄位
                                    if ($state) {
                                        $date = Carbon::parse($state);
                                        $set('year', $date->year);
                                    }
                                }),

                            // [2. 類型]
                            Select::make('type')
                                ->label('類型')
                                ->options([
                                    NationalHoliday::TYPE_HOLIDAY => '放假 (Holiday)',
                                    NationalHoliday::TYPE_MAKEUP => '補班/補課 (Makeup)',
                                ])
                                ->required()
                                ->default(NationalHoliday::TYPE_HOLIDAY)
                                ->native(false),
                        ]),

                        // [3. 名稱]
                        TextInput::make('name')
                            ->label('節日名稱')
                            ->placeholder('ex: 元旦、春節補假')
                            ->required()
                            ->maxLength(255)
                            ->columnSpanFull(),

                        // [4. 年份 (顯示用)]
                        // 雖然 Model 的 booted() 會自動寫入，但在前端先算出來給管理員看比較安心
                        TextInput::make('year')
                            ->label('年份')
                            ->disabled() // 禁止手動改，避免改錯
                            ->dehydrated() // 宗師指點：設為 true 才會送出，若 Model 有自動算可設 false
                            ->hint('系統會根據日期自動填入')
                            ->numeric(),
                    ]),
            ]);
    }
}
