<?php

namespace App\Filament\Resources\Staff\Tables;

use App\Enums\DietaryType;
use App\Enums\SemesterStage;
use App\Enums\StaffRole;
use App\Filament\Resources\Staff\StaffResource;
use App\Models\Staff;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn; // [新增]
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table; // [新增]

class StaffTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                // 姓名 + 職稱
                TextColumn::make('name')
                    ->label('姓名')
                    ->description(fn (Staff $record) => $record->job_title)
                    ->searchable(['name', 'job_title'])
                    ->sortable()
                    ->weight('bold'),

                // 卡號
                TextColumn::make('lunch_code')
                    ->label('卡號')
                    ->icon('heroicon-m-qr-code')
                    ->copyable()
                    ->searchable()
                    ->color('gray')
                    ->toggleable(isToggledHiddenByDefault: true), // 預設隱藏

                // 身分
                TextColumn::make('staff_role')
                    ->label('身分')
                    ->badge()
                    ->sortable(),

                // 部別 (國小/幼兒園)
                TextColumn::make('stage')
                    ->label('部別')
                    ->badge()
                    ->sortable(),

                // 飲食習慣
                TextColumn::make('dietary_type')
                    ->label('飲食')
                    ->badge()
                    ->color(fn (DietaryType $state) => match ($state) {
                        DietaryType::Meat => 'gray',
                        DietaryType::Vegan => 'success', // 素食亮綠色
                        default => 'warning',
                    }),

                // 搭伙狀態 (改用 IconColumn 或 ToggleColumn，這裡示範 IconColumn 保持一致)
                IconColumn::make('is_meal_user')
                    ->label('搭伙')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle')
                    ->sortable(),

                // 在職狀態 (改用 ToggleColumn 直接切換)
                ToggleColumn::make('is_active')
                    ->label('在職')
                    ->sortable(),
            ])
            ->filters([
                TrashedFilter::make(),

                SelectFilter::make('staff_role')
                    ->label('身分')
                    ->options(StaffRole::class),

                SelectFilter::make('stage')
                    ->label('部別')
                    ->options(SemesterStage::class),

                SelectFilter::make('dietary_type')
                    ->label('飲食')
                    ->options(DietaryType::class),

                TernaryFilter::make('is_meal_user')
                    ->label('搭伙狀態'),
            ])
            ->actions([
                EditAction::make()
                    ->slideOver() // 從右側滑出，不離開列表頁
                    ->modalWidth('2xl'),
                Action::make('activities')
                    ->label('異動紀錄')
                    ->icon('heroicon-m-clock')
                    ->color('info')
                    ->url(fn ($record) => StaffResource::getUrl('activities', ['record' => $record])),
                DeleteAction::make(),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                    ForceDeleteBulkAction::make(),
                    RestoreBulkAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }
}
