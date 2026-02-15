<?php

namespace App\Filament\Resources\LineAccounts\Tables;

use App\Models\LineAccount;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

class LineAccountsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                ImageColumn::make('avatar_url')
                    ->label('頭像')
                    ->circular(),

                TextColumn::make('display_name')
                    ->label('LINE 顯示名稱')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),

                TextColumn::make('user.name')
                    ->label('綁定系統使用者')
                    ->description(fn(LineAccount $record) => $record->user?->email)
                    ->searchable()
                    ->sortable(),

                TextColumn::make('line_user_id')
                    ->label('LINE UID')
                    ->copyable()
                    ->toggleable(),

                TextColumn::make('created_at')
                    ->label('綁定時間')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                TernaryFilter::make('is_bound')
                    ->label('綁定狀態')
                    ->attribute('user_id')
                    ->nullable(), // 雖然理論上都有 user_id，但若允許暫存無主帳號
            ])
            ->actions([
                ViewAction::make(),
                EditAction::make(),

                Action::make('unbind')
                    ->label('解除綁定')
                    ->color('danger')
                    ->icon('heroicon-m-link-slash')
                    ->requiresConfirmation()
                    ->action(function (LineAccount $record) {
                        // 這裡可以選擇刪除 LineAccount 或是設 user_id 為 null
                        // 根據業務邏輯，LineAccount 若無 user_id 通常無意義，故刪除
                        $record->delete();
                    }),
            ])
            ->bulkActions([
                // 通常不允許批量刪除帳號，除非確定
                DeleteBulkAction::make(),
            ])
            ->defaultSort('created_at', 'desc');
    }
}
