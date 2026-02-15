<?php

namespace App\Filament\Resources\Meals\Schemas;

use App\Enums\Meals\CancelLeaveType;
use App\Enums\Meals\CancelRequestStatus;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class MealCancelRequestForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('申請資訊')
                    ->description('填寫學生停餐申請資訊')
                    ->schema([
                        Grid::make(3)
                            ->schema([
                                Select::make('student_id')
                                    ->label('學生')
                                    ->relationship('student', 'name')
                                    ->searchable()
                                    ->preload()
                                    ->required()
                                    ->columnSpan(1),

                                DatePicker::make('start_date')
                                    ->label('開始日期')
                                    ->required()
                                    ->columnSpan(1),

                                DatePicker::make('end_date')
                                    ->label('結束日期')
                                    ->columnSpan(1),
                            ]),

                        Grid::make(3)
                            ->schema([
                                Select::make('leave_type')
                                    ->label('假別/原因')
                                    ->options(CancelLeaveType::class)
                                    ->required()
                                    ->columnSpan(1),

                                Select::make('status')
                                    ->label('狀態')
                                    ->options(CancelRequestStatus::class)
                                    ->default(CancelRequestStatus::Pending)
                                    ->required()
                                    ->columnSpan(1),

                                TextInput::make('origin')
                                    ->label('來源')
                                    ->default('admin')
                                    ->readOnly()
                                    ->columnSpan(1),
                            ]),

                        Textarea::make('reason')
                            ->label('詳細說明')
                            ->rows(3)
                            ->columnSpanFull(),
                    ]),
            ]);
    }
}
