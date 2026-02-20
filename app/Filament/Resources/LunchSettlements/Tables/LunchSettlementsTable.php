<?php

namespace App\Filament\Resources\LunchSettlements\Tables;

use App\Jobs\SendBillingNotification;
use App\Models\LunchSettlement;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Collection;

class LunchSettlementsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('year_month')
                    ->label('月份')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('staff.name')
                    ->label('教職員')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('days_count')
                    ->label('用餐天數')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('amount')
                    ->label('應繳金額')
                    ->money('TWD')
                    ->sortable(),
                TextColumn::make('status')
                    ->label('狀態')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'draft' => '草稿',
                        'sent' => '已通知',
                        'paid' => '已結清',
                        default => $state,
                    })
                    ->color(fn (string $state): string => match ($state) {
                        'draft' => 'gray',
                        'sent' => 'warning',
                        'paid' => 'success',
                        default => 'gray',
                    }),
            ])
            ->defaultSort('year_month', 'desc')
            ->filters([
                SelectFilter::make('year_month')
                    ->label('月份')
                    ->options(fn () => LunchSettlement::query()
                        ->select('year_month')
                        ->distinct()
                        ->orderBy('year_month', 'desc')
                        ->pluck('year_month', 'year_month')
                        ->toArray()),
            ])
            ->headerActions([
                \Filament\Actions\Action::make('refresh')
                    ->label('計算本月結算')
                    ->form([
                        \Filament\Forms\Components\Select::make('year_month')
                            ->label('選擇月份')
                            ->options(function () {
                                $options = [];
                                for ($i = 0; $i < 6; $i++) {
                                    $date = now()->subMonths($i)->format('Y-m');
                                    $options[$date] = $date;
                                }

                                return $options;
                            })
                            ->default(now()->subMonth()->format('Y-m'))
                            ->required(),
                    ])
                    ->action(function (array $data) {
                        $service = new \App\Services\LunchSettlementService;
                        // 假設目前 context 為第一個學校，或需讓 User 選擇學校
                        // 這裡簡化為：遍歷所有學校或特定學校
                        // 由於是 Multi-tenancy，理論上 current tenant 已經 filter
                        // 但此 resource 看起來是全域的？先抓取所有學校
                        $schools = \App\Models\School::all();
                        foreach ($schools as $school) {
                            $service->calculateMonthlySettlement($school, $data['year_month']);
                        }

                        Notification::make()
                            ->title('結算完成')
                            ->success()
                            ->send();
                    }),
            ])
            ->actions([
                EditAction::make(),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                    BulkAction::make('send_notification')
                        ->label('發送 LINE 通知')
                        ->icon('heroicon-o-paper-airplane')
                        ->requiresConfirmation()
                        ->action(function (Collection $records) {
                            /** @var \App\Models\LunchSettlement $record */
                            foreach ($records as $record) {
                                // Dispatch Job
                                SendBillingNotification::dispatch($record);
                            }

                            Notification::make()
                                ->title('已排程發送通知')
                                ->success()
                                ->send();
                        }),
                ]),
            ]);
    }
}
