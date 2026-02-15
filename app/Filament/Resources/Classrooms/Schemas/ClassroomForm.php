<?php

namespace App\Filament\Resources\Classrooms\Schemas;

use App\Enums\GradeLevel;
use App\Enums\SemesterStage;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class ClassroomForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                // === 左側主區塊 (2/3) ===
                Group::make()
                    ->schema([
                        Section::make('班級基本資料')
                            ->icon('heroicon-m-academic-cap')
                            ->schema([
                                // 1. 學校選擇 (多租戶邏輯 - 保持一致性)
                                Select::make('school_id')
                                    ->label('所屬學校')
                                    ->relationship('school', 'name')
                                    ->default(fn () => auth()->user()->school_id)
                                    ->required()
                                    ->selectablePlaceholder(false)
                                    ->disabled(fn () => ! auth()->user()->canBypassSchoolScope())
                                    ->dehydrated()
                                    ->columnSpanFull(),

                                Grid::make(2)->schema([
                                    Select::make('stage')
                                        ->label('學制/部別')
                                        ->options(SemesterStage::class)
                                        ->required()
                                        ->native(false)
                                        ->default(SemesterStage::Elementary),

                                    Select::make('grade')
                                        ->label('年級')
                                        ->options(GradeLevel::class)
                                        ->required()
                                        ->native(false),
                                ]),

                                TextInput::make('class_name')
                                    ->label('班級名稱')
                                    ->placeholder('例：甲班、一班、彩虹班')
                                    ->required()
                                    ->maxLength(20),

                                Select::make('school_staff_id')
                                    ->label('班導師')
                                    ->relationship('teacher', 'name', fn ($query) => $query->where('school_id', auth()->user()->school_id))
                                    ->searchable()
                                    ->preload()
                                    ->placeholder('請選擇班導師...'),

                                Select::make('caterer_id')
                                    ->label('預設供餐業者')
                                    ->relationship('caterer', 'name', fn ($query) => $query->where('school_id', auth()->user()->school_id))
                                    ->searchable()
                                    ->preload()
                                    ->placeholder('請選擇供餐業者...'),
                            ]),
                    ])
                    ->columnSpan(['lg' => 2]),

                // === 右側設定區 (1/3) ===
                Group::make()
                    ->schema([
                        Section::make('狀態與設定')
                            ->icon('heroicon-m-cog')
                            ->schema([
                                Toggle::make('is_active')
                                    ->label('啟用狀態')
                                    ->default(true)
                                    ->onColor('success')
                                    ->offColor('danger'),

                                TextInput::make('teacher_meal_qty')
                                    ->label('導師餐份數')
                                    ->numeric()
                                    ->default(1)
                                    ->minValue(0)
                                    ->required(),

                                TextInput::make('location')
                                    ->label('教室位置')
                                    ->placeholder('例：教學大樓 201')
                                    ->maxLength(100),

                                TextInput::make('sort_order')
                                    ->label('排序權重')
                                    ->numeric()
                                    ->disabled()
                                    ->helperText('系統自動計算'),
                            ]),
                    ])
                    ->columnSpan(['lg' => 1]),
            ])
            ->columns(3);
    }
}
