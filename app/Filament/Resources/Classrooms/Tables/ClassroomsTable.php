<?php

namespace App\Filament\Resources\Classrooms\Tables;

use App\Enums\GradeLevel;
use App\Enums\SemesterStage;
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

class ClassroomsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('stage')
                    ->label('部別')
                    ->badge()
                    ->sortable(),

                TextColumn::make('grade')
                    ->label('年級')
                    ->badge()
                    ->sortable(),

                TextColumn::make('class_name')
                    ->label('班級名稱')
                    ->weight('bold')
                    ->color('primary')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('teacher.name')
                    ->label('班導師')
                    ->icon('heroicon-m-user')
                    ->searchable()
                    ->default('未定'),

                TextColumn::make('students_count')
                    ->label('學生數')
                    ->counts('students')
                    ->badge()
                    ->color('info')
                    ->sortable(),

                ToggleColumn::make('is_active')
                    ->label('啟用狀態')
                    ->sortable(),

                TextColumn::make('sort_order')
                    ->label('排序權重')
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->sortable(),
            ])
            ->filters([
                TrashedFilter::make(),

                SelectFilter::make('stage')
                    ->label('部別')
                    ->options(SemesterStage::class),

                SelectFilter::make('grade')
                    ->label('年級')
                    ->options(GradeLevel::class),

                TernaryFilter::make('is_active')
                    ->label('啟用狀態'),
            ])
            ->actions([
                EditAction::make()
                    ->slideOver()
                    ->modalWidth('2xl'),
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
            ->defaultSort('sort_order', 'asc');
    }
}
