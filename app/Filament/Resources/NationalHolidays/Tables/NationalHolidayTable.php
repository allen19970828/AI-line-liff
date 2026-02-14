<?php

namespace App\Filament\Resources\NationalHolidays\Tables;

use App\Models\NationalHoliday;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class NationalHolidayTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                // [1. 日期]
                TextColumn::make('date')
                    ->label('日期')
                    ->date('Y-m-d')
                    ->sortable()
                    ->searchable()
                    ->weight('bold'),

                // [2. 名稱]
                TextColumn::make('name')
                    ->label('節日名稱')
                    ->searchable()
                    ->limit(30),

                // [3. 類型 (Badge)]
                TextColumn::make('type')
                    ->label('類型')
                    ->badge()
                    ->formatStateUsing(fn(NationalHoliday $record) => $record->getTypeName())
                    ->color(fn(NationalHoliday $record) => $record->getColor())
                    ->sortable(),

                // [4. 年份]
                TextColumn::make('year')
                    ->label('年份')
                    ->sortable()
                    ->color('gray'),
            ])
            ->defaultSort('date', 'desc') // 預設顯示最新的
            ->filters([
                // [年份過濾器]
                // 動態撈取資料庫中有的年份
                SelectFilter::make('year')
                    ->label('年份')
                    ->options(
                        fn() => NationalHoliday::query()
                            ->select('year')
                            ->distinct()
                            ->orderBy('year', 'desc')
                            ->pluck('year', 'year')
                            ->toArray()
                    ),

                // [類型過濾器]
                SelectFilter::make('type')
                    ->label('類型')
                    ->options([
                        NationalHoliday::TYPE_HOLIDAY => '放假',
                        NationalHoliday::TYPE_MAKEUP => '補班/補課',
                    ]),
            ])
            ->actions([
                \Filament\Actions\Action::make('activities')
                    ->label('異動紀錄')
                    ->icon('heroicon-m-clock')
                    ->color('info')
                    ->url(fn($record) => \App\Filament\Resources\NationalHolidays\NationalHolidayResource::getUrl('activities', ['record' => $record])),
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
