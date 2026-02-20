<?php

namespace App\Filament\Resources\Semesters\Tables;

use App\Enums\SemesterStage;
use App\Models\Semester;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;

class SemestersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('code')
                    ->label('代碼')
                    ->sortable()
                    ->searchable()
                    ->weight('bold')
                    ->color('primary')
                    ->copyable(),

                TextColumn::make('name')
                    ->label('學期名稱')
                    ->sortable()
                    ->searchable(),

                // 宗師指點：使用 Enum 的 badge 威力
                TextColumn::make('stage')
                    ->label('部別')
                    ->badge()
                    ->sortable(),

                TextColumn::make('start_date')
                    ->label('起訖日期')
                    ->formatStateUsing(
                        fn (Semester $record) => sprintf(
                            '%s ~ %s',
                            $record->start_date instanceof \Carbon\Carbon ? $record->start_date->format('Y/m/d') : '-',
                            $record->end_date instanceof \Carbon\Carbon ? $record->end_date->format('Y/m/d') : '-'
                        )
                    ),

                // 直接在列表開關，霸氣！
                ToggleColumn::make('is_active')
                    ->label('啟用'),

                TextColumn::make('meal_price')
                    ->label('定價')
                    ->money('TWD')
                    ->sortable(),
            ])
            ->filters([
                TrashedFilter::make(),

                SelectFilter::make('stage')
                    ->label('部別')
                    ->options(SemesterStage::class),

                TernaryFilter::make('is_active')
                    ->label('啟用狀態'),
            ])
            ->actions([
                \Filament\Actions\Action::make('activities')
                    ->label('異動紀錄')
                    ->icon('heroicon-m-clock')
                    ->color('info')
                    ->url(fn ($record) => \App\Filament\Resources\Semesters\SemesterResource::getUrl('activities', ['record' => $record])),
                EditAction::make(),
                DeleteAction::make(),
                RestoreAction::make(),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                    RestoreBulkAction::make(),
                    ForceDeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('start_date', 'desc'); // 預設顯示最新的學期
    }
}
