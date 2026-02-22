<?php

namespace App\Filament\Resources\Meals\Tables;

use App\Enums\Meals\CancelRequestStatus;
use App\Models\MealCancelRequest;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
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
                TextColumn::make('cancellable.name')
                    ->label('申請人姓名')
                    ->sortable()
                    ->searchable(),

                TextColumn::make('classroom_info')
                    ->label('班級座號')
                    ->state(fn (MealCancelRequest $record) => match ($record->cancellable_type) {
                        \App\Models\Student::class => $record->cancellable->class_full_name.' ('.$record->cancellable->seat_number.'號)',
                        default => '-',
                    })
                    ->badge()
                    ->color('gray'),

                TextColumn::make('cancellable_type')
                    ->label('類型')
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        \App\Models\Student::class => '學生',
                        \App\Models\Staff::class => '教職員',
                        default => $state,
                    })
                    ->badge()
                    ->sortable(),

                TextColumn::make('start_date')
                    ->label('開始日期')
                    ->date('Y/m/d')
                    ->sortable(),

                TextColumn::make('end_date')
                    ->label('結束日期')
                    ->date('Y/m/d')
                    ->sortable(),

                TextColumn::make('duration')
                    ->label('停餐天數')
                    ->state(function (MealCancelRequest $record): string {
                        // 尋找對應學期
                        $semester = \App\Models\Semester::where('school_id', $record->cancellable->school_id)
                            ->whereDate('start_date', '<=', $record->start_date)
                            ->whereDate('end_date', '>=', $record->end_date)
                            ->first();

                        if (! $semester) {
                            return 'N/A';
                        }

                        // 統一使用 MealService 計算有效停餐天數
                        $mealDays = ($record->cancellable_type === \App\Models\Student::class)
                            ? $record->cancellable?->meal_days
                            : null;

                        $days = \App\Services\MealService::calculateEffectiveCancellationDays(
                            $semester,
                            $record->start_date,
                            $record->end_date,
                            $mealDays
                        );

                        return "{$days} 天";
                    })
                    ->badge()
                    ->color('info'),

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
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->defaultGroup(
                \Filament\Tables\Grouping\Group::make('classroom.sort_order')
                    ->label('班級')
                    ->getTitleFromRecordUsing(fn ($record) => $record->classroom?->full_name ?? '未分班')
            )
            ->groups([
                \Filament\Tables\Grouping\Group::make('classroom.sort_order')
                    ->label('班級')
                    ->getTitleFromRecordUsing(fn ($record) => $record->classroom?->full_name ?? '未分班'),
                'start_date',
                'cancellable_type',
            ]);
    }
}
