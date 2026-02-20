<?php

namespace App\Filament\Resources\LunchSettlements\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TagsInput;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class LunchSettlementForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('結算資訊')
                    ->columns(2)
                    ->schema([
                        Select::make('school_id')
                            ->relationship('school', 'name')
                            ->label('學校')
                            ->disabled()
                            ->required(),
                        Select::make('staff_id')
                            ->relationship('staff', 'name')
                            ->label('教職員')
                            ->disabled()
                            ->required(),
                        Select::make('semester_id')
                            ->relationship('semester', 'name')
                            ->label('學期')
                            ->disabled()
                            ->required(),
                        TextInput::make('year_month')
                            ->label('月份')
                            ->disabled()
                            ->required(),
                        TextInput::make('days_count')
                            ->label('用餐天數')
                            ->numeric()
                            ->readOnly(),
                        TextInput::make('snapshot_price')
                            ->label('當期餐費單價')
                            ->numeric()
                            ->prefix('$')
                            ->readOnly(),
                        TextInput::make('amount')
                            ->label('總金額')
                            ->numeric()
                            ->prefix('$')
                            ->readOnly() // 暫時設為唯讀，若需手動調整可之後開放
                            ->columnSpanFull(),
                    ]),

                Section::make('狀態與明細')
                    ->schema([
                        Select::make('status')
                            ->label('狀態')
                            ->options([
                                'draft' => '草稿',
                                'sent' => '已通知',
                                'paid' => '已結清',
                            ])
                            ->required(),
                        TagsInput::make('check_in_dates')
                            ->label('打卡日期明細')
                            ->disabled() // 這是 JSON 資料，不建議直接手動改
                            ->columnSpanFull(),
                    ]),
            ]);
    }
}
