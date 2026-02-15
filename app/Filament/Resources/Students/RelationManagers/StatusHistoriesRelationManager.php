<?php

namespace App\Filament\Resources\Students\RelationManagers;

use App\Enums\StudentIdentity;
use App\Enums\StudentStatus;
use App\Enums\SubsidyReason;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;

class StatusHistoriesRelationManager extends RelationManager
{
    protected static string $relationship = 'statusHistories';

    protected static ?string $title = '身分與狀態歷程';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Grid::make(2)->schema([
                    Forms\Components\DatePicker::make('start_date')
                        ->label('生效日期')
                        ->required(),

                    Forms\Components\DatePicker::make('end_date')
                        ->label('結束日期')
                        ->helperText('留空代表至今有效'),

                    Forms\Components\Select::make('status')
                        ->label('當時狀態')
                        ->options(StudentStatus::class)
                        ->required(),

                    Forms\Components\Select::make('identity')
                        ->label('當時身分')
                        ->options(StudentIdentity::class)
                        ->required()
                        ->live(),

                    Forms\Components\Select::make('subsidy_reason')
                        ->label('當時補助原因')
                        ->options(SubsidyReason::class)
                        ->visible(fn (Get $get) => $get('identity') != StudentIdentity::SELF_PAY->value),

                    Forms\Components\TextInput::make('note')
                        ->label('異動備註')
                        ->columnSpanFull(),
                ]),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('note')
            ->columns([
                Tables\Columns\TextColumn::make('start_date')
                    ->label('生效日')
                    ->date()
                    ->sortable(),

                Tables\Columns\TextColumn::make('end_date')
                    ->label('結束日')
                    ->date()
                    ->placeholder('至今'),

                Tables\Columns\TextColumn::make('status')
                    ->label('狀態')
                    ->badge(),

                Tables\Columns\TextColumn::make('identity')
                    ->label('身分')
                    ->badge(),

                Tables\Columns\TextColumn::make('subsidy_reason')
                    ->label('補助原因')
                    ->badge(),
            ])
            ->defaultSort('start_date', 'desc') // 讓最新的紀錄排在最上面
            ->headerActions([
                CreateAction::make()
                    ->label('手動補登歷程'),
            ])
            ->actions([
                EditAction::make(),
                DeleteAction::make(),
            ]);
    }
}
