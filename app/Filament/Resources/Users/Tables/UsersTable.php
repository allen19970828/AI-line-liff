<?php

namespace App\Filament\Resources\Users\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class UsersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                // 1. 姓名 (可搜尋)
                TextColumn::make('name')
                    ->label('姓名')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),

                // 2. Email (可複製)
                TextColumn::make('email')
                    ->label('電子郵件')
                    ->searchable()
                    ->icon('heroicon-m-envelope')
                    ->copyable()
                    ->copyMessage('Email 已複製')
                    ->color('gray'),

                // 3. 角色顯示 (Shield 核心) - 使用 Badge 樣式
                TextColumn::make('roles.name')
                    ->label('職位角色')
                    ->badge()
                    ->color(fn(string $state): string => match ($state) {
                        'super_admin' => 'danger',
                        'school_admin' => 'warning',
                        'panel_user' => 'success',
                        default => 'gray',
                    })
                    ->separator(','),

                // 4. 學校顯示 (多租戶核心)
                TextColumn::make('school.name')
                    ->label('所屬學校')
                    ->searchable(),

                // 5. 建立時間
                TextColumn::make('created_at')
                    ->label('建立時間')
                    ->dateTime('Y-m-d H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('roles')
                    ->label('依角色篩選')
                    ->relationship('roles', 'name'),
            ])
            ->actions([
                \Filament\Actions\Action::make('activities')
                    ->label('異動紀錄')
                    ->icon('heroicon-m-clock')
                    ->color('info')
                    ->url(fn($record) => \App\Filament\Resources\Users\UserResource::getUrl('activities', ['record' => $record])),
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
