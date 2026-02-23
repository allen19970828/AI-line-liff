<?php

namespace App\Filament\Resources\SubsidyItems\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;

class SubsidyItemsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('school.name')
                    ->label('所屬學校')
                    ->sortable()
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('semester.name')
                    ->label('適用學期')
                    ->sortable()
                    ->searchable(),
                TextColumn::make('name')
                    ->label('補貼名稱')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('code')
                    ->label('代碼')
                    ->badge()
                    ->color('gray')
                    ->searchable(),
                TextColumn::make('source_type')
                    ->label('補助來源')
                    ->badge()
                    ->sortable()
                    ->searchable(),
                IconColumn::make('is_full_coverage')
                    ->label('全額補助')
                    ->boolean()
                    ->sortable(),
                TextColumn::make('amount_per_meal')
                    ->label('每餐單價')
                    ->money('TWD')
                    ->sortable(),
                TextColumn::make('budget_limit')
                    ->label('總預算')
                    ->money('TWD')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('priority')
                    ->label('權重')
                    ->numeric()
                    ->sortable(),
                \Filament\Tables\Columns\ToggleColumn::make('is_active')
                    ->label('狀態')
                    ->sortable(),
                TextColumn::make('updated_at')
                    ->label('更新時間')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                \Filament\Tables\Filters\SelectFilter::make('semester_id')
                    ->label('學期')
                    ->relationship('semester', 'name'),
                \Filament\Tables\Filters\SelectFilter::make('source_type')
                    ->label('補助來源')
                    ->options(\App\Enums\SubsidySource::class),
                TrashedFilter::make(),
            ])
            ->actions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                    ForceDeleteBulkAction::make(),
                    RestoreBulkAction::make(),
                ]),
            ]);
    }
}
