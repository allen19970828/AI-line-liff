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
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Builder;

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
                                Select::make('cancellable_type')
                                    ->label('申請人類型')
                                    ->options([
                                        \App\Models\Student::class => '學生',
                                        \App\Models\Staff::class => '教職員',
                                    ])
                                    ->required()
                                    ->reactive()
                                    ->columnSpan(1),

                                Select::make('cancellable_id')
                                    ->label('申請人姓名')
                                    ->relationship(
                                        name: 'cancellable',
                                        titleAttribute: 'name',
                                        modifyQueryUsing: function (Builder $query, Get $get) {
                                            $type = $get('cancellable_type');
                                            if ($type) {
                                                return $query; // Filament handles the type filtering if name matches morphTo
                                            }

                                            return $query->whereRaw('1=0');
                                        }
                                    )
                                    ->searchable()
                                    ->preload()
                                    ->required()
                                    ->hidden(fn (Get $get) => ! $get('cancellable_type'))
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
