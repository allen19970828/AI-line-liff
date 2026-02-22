<?php

namespace App\Filament\Resources;

use App\Filament\Resources\AuthenticationLogResource\Pages;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Rappasoft\LaravelAuthenticationLog\Models\AuthenticationLog;

class AuthenticationLogResource extends Resource
{
    // 指定這是對應到 AuthenticationLog 模型
    protected static ?string $model = AuthenticationLog::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-finger-print';

    protected static ?string $navigationLabel = '登入稽核日誌';

    protected static ?string $modelLabel = '登入紀錄';

    protected static ?string $pluralModelLabel = '登入紀錄';

    protected static string|\UnitEnum|null $navigationGroup = '系統管理';

    /**
     * [安全] 多租戶隔離：透過 User 關聯間接過濾
     * AuthenticationLog 沒有 school_id，但可透過 authenticatable (User) 關聯過濾
     */
    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();

        $user = auth()->user();

        if ($user && ! $user->canBypassSchoolScope() && $user->school_id) {
            $query->whereHasMorph(
                'authenticatable',
                [\App\Models\User::class],
                fn (Builder $q) => $q->where('school_id', $user->school_id)
            );
        }

        return $query;
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                // 1. 登入者 (解決顯示 "User" 的問題)
                Tables\Columns\TextColumn::make('authenticatable.name')
                    ->label('使用者')
                    ->searchable()
                    ->sortable()
                    ->description(fn ($record) => $record->authenticatable?->email),

                // 2. IP 位址
                Tables\Columns\TextColumn::make('ip_address')
                    ->label('IP 位址')
                    ->searchable()
                    ->copyable(),

                // 3. 瀏覽器/裝置
                Tables\Columns\TextColumn::make('user_agent')
                    ->label('裝置資訊')
                    ->searchable()
                    ->limit(30)
                    ->tooltip(fn ($state) => $state),

                // 4. 登入時間
                Tables\Columns\TextColumn::make('login_at')
                    ->label('登入時間')
                    ->dateTime('Y-m-d H:i:s')
                    ->sortable(),

                // 5. 登入狀態
                Tables\Columns\IconColumn::make('login_successful')
                    ->label('狀態')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle')
                    ->trueColor('success')
                    ->falseColor('danger'),

                // 6. 登出時間
                Tables\Columns\TextColumn::make('logout_at')
                    ->label('登出時間')
                    ->dateTime('Y-m-d H:i:s')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('login_at', 'desc')
            ->filters([
                //
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListAuthenticationLogs::route('/'),
        ];
    }
}
