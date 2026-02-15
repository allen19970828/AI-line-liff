<?php

namespace App\Filament\Resources\Meals\Tables;

use App\Enums\Meals\CancelRequestStatus;
use App\Models\MealCancelRequest;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class MealCancelRequestsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('student.name')
                    ->label('學生姓名')
                    ->sortable()
                    ->searchable(),

                TextColumn::make('student.student_code')
                    ->label('學號')
                    ->sortable()
                    ->searchable(),

                TextColumn::make('start_date')
                    ->label('開始日期')
                    ->date('Y/m/d')
                    ->sortable(),

                TextColumn::make('end_date')
                    ->label('結束日期')
                    ->date('Y/m/d')
                    ->sortable(),

                TextColumn::make('leave_type')
                    ->label('假別')
                    ->sortable()
                    ->badge(),

                TextColumn::make('status')
                    ->label('狀態')
                    ->sortable()
                    ->badge(),

                TextColumn::make('origin')
                    ->label('來源')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'liff' => 'success',
                        'admin' => 'info',
                        default => 'gray',
                    }),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label('狀態')
                    ->options(CancelRequestStatus::class),

                Filter::make('pending')
                    ->label('待審核')
                    ->query(fn (Builder $query) => $query->where('status', CancelRequestStatus::Pending)),
            ])
            ->actions([
                Action::make('approve')
                    ->label('核准')
                    ->color('success')
                    ->icon('heroicon-o-check')
                    ->requiresConfirmation()
                    ->action(fn (MealCancelRequest $record) => $record->update(['status' => CancelRequestStatus::Approved]))
                    ->visible(fn (MealCancelRequest $record) => $record->status === CancelRequestStatus::Pending),

                Action::make('reject')
                    ->label('駁回')
                    ->color('danger')
                    ->icon('heroicon-o-x-mark')
                    ->requiresConfirmation()
                    ->action(fn (MealCancelRequest $record) => $record->update(['status' => CancelRequestStatus::Rejected]))
                    ->visible(fn (MealCancelRequest $record) => $record->status === CancelRequestStatus::Pending),

                EditAction::make(),
                DeleteAction::make(),
            ])
            ->bulkActions([
                //
            ]);
    }
}
