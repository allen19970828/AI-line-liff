<?php

namespace App\Filament\Resources\Caterers\Tables;

use App\Enums\CatererSupplyType;
use App\Filament\Resources\Caterers\CatererResource;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

class CaterersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                // 1. 廠商名稱 (可搜尋)
                TextColumn::make('name')
                    ->label('廠商名稱')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),

                // 2. 供應類型 (用 Enum 自動轉 Badge)
                TextColumn::make('supply_type')
                    ->label('類型')
                    ->badge() // 變成膠囊狀
                    ->sortable(),

                // 3. 聯絡人與電話 (合併顯示，節省空間)
                TextColumn::make('contact_info')
                    ->label('聯絡窗口')
                    ->getStateUsing(fn ($record) => $record->contact_person)
                    ->description(fn ($record) => $record->phone)
                    ->icon('heroicon-m-user-circle'),

                // 4. 統編
                TextColumn::make('tax_id')
                    ->label('統編')
                    ->searchable()
                    ->color('gray')
                    ->toggleable(isToggledHiddenByDefault: true), // 預設隱藏，需要再開

                // 5. 啟用狀態 (可直接點擊切換)
                ToggleColumn::make('is_active')
                    ->label('啟用')
                    ->sortable(),
            ])
            ->filters([
                // 依類型篩選
                SelectFilter::make('supply_type')
                    ->label('供應類型')
                    ->options(CatererSupplyType::class),

                // 依狀態篩選
                TernaryFilter::make('is_active')
                    ->label('啟用狀態'),
            ])
            ->actions([
                EditAction::make(),
                Action::make('activities')
                    ->label('異動紀錄')
                    ->icon('heroicon-m-clock')
                    ->color('info')
                    ->url(fn ($record) => CatererResource::getUrl('activities', ['record' => $record])),
                DeleteAction::make(),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
