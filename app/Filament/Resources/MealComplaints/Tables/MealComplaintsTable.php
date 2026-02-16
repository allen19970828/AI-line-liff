<?php

namespace App\Filament\Resources\MealComplaints\Tables;

use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Actions\EditAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;

class MealComplaintsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('school.name')
                    ->label('學校')
                    ->searchable()
                    ->sortable()
                    ->toggleable(),
                TextColumn::make('classroom.class_name')
                    ->label('班級')
                    ->searchable()
                    ->placeholder('全校性'),
                TextColumn::make('staff.name')
                    ->label('回報者')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('occurred_at')
                    ->label('發生日期')
                    ->date()
                    ->sortable(),
                ImageColumn::make('photo_path')
                    ->label('證據照片')
                    ->disk('public') // 視您的設定而定
                    ->square(),
                TextColumn::make('status')
                    ->label('狀態')
                    ->badge()
                    ->formatStateUsing(fn(string $state): string => match ($state) {
                        'pending' => '待處理',
                        'processing' => '處理中',
                        'resolved' => '已結案',
                        default => $state,
                    })
                    ->color(fn(string $state): string => match ($state) {
                        'pending' => 'danger',
                        'processing' => 'warning',
                        'resolved' => 'success',
                        default => 'gray',
                    }),
                TextColumn::make('resolved_at')
                    ->label('結案時間')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('created_at')
                    ->label('建立時間')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('school_id')
                    ->label('學校')
                    ->relationship('school', 'name'),
                SelectFilter::make('status')
                    ->label('狀態')
                    ->options([
                        'pending' => '待處理',
                        'processing' => '處理中',
                        'resolved' => '已結案',
                    ]),
            ])
            ->actions([
                EditAction::make(),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
