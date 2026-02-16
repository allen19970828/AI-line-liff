<?php

namespace App\Filament\Resources\MealComplaints\Schemas;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class MealComplaintForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('案件資訊')
                    ->description('記錄問題發生的時間、地點與回報人')
                    ->schema([
                        Grid::make(3)
                            ->schema([
                                Select::make('school_id')
                                    ->relationship('school', 'name')
                                    ->required()
                                    ->disabled()
                                    ->dehydrated(),
                                Select::make('classroom_id')
                                    ->relationship('classroom', 'class_name')
                                    ->placeholder('全校性問題則留空'),
                                Select::make('staff_id')
                                    ->relationship('staff', 'name')
                                    ->required()
                                    ->searchable(),
                            ]),
                        DatePicker::make('occurred_at')
                            ->label('發生日期')
                            ->required()
                            ->default(now()),
                    ]),

                Section::make('問題內容與證據')
                    ->schema([
                        Textarea::make('content')
                            ->label('問題描述')
                            ->required()
                            ->rows(5)
                            ->columnSpanFull(),
                        FileUpload::make('photo_path')
                            ->label('食安證據照片')
                            ->image()
                            ->directory('complaints')
                            ->visibility('public')
                            ->columnSpanFull(),
                    ]),

                Section::make('處理進度')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                Select::make('status')
                                    ->label('處理狀態')
                                    ->options([
                                        'pending' => '待處理',
                                        'processing' => '處理中',
                                        'resolved' => '已結案',
                                    ])
                                    ->required()
                                    ->default('pending')
                                    ->native(false),
                                DateTimePicker::make('resolved_at')
                                    ->label('結案時間'),
                            ]),
                        Textarea::make('caterer_reply')
                            ->label('廠商回覆內容')
                            ->rows(3)
                            ->placeholder('記錄廠商的說明或補償方案')
                            ->columnSpanFull(),
                    ]),
            ]);
    }
}
