<?php

namespace App\Filament\Resources\Users\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Hash;

class UserForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                // --- 左側：核心帳號區 ---
                Section::make('帳號核心資訊')
                    ->description('管理使用者的登入憑證')
                    ->icon('heroicon-o-user')
                    ->schema([
                        TextInput::make('name')
                            ->label('姓名')
                            ->required()
                            ->maxLength(255),

                        TextInput::make('email')
                            ->label('電子郵件')
                            ->email()
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->maxLength(255),

                        // 宗師指點：密碼處理黃金法則
                        TextInput::make('password')
                            ->label('密碼')
                            ->password()
                            ->revealable()
                            ->dehydrateStateUsing(fn ($state) => Hash::make($state))
                            ->dehydrated(fn ($state) => filled($state))
                            ->required(fn (string $context): bool => $context === 'create')
                            ->helperText('建立時必填；編輯時若不修改請留空。'),
                    ])->columns(2),

                // --- 右側：權限與歸屬區 ---
                Section::make('權限與歸屬')
                    ->description('設定該使用者的職位與管轄範圍')
                    ->icon('heroicon-o-shield-check')
                    ->schema([
                        // 1. Filament Shield 角色選擇器
                        Select::make('roles')
                            ->label('職位角色 (Shield)')
                            ->relationship('roles', 'name')
                            ->multiple()
                            ->preload()
                            ->searchable(),

                        // 2. 學校歸屬 (多租戶關鍵)
                        Select::make('school_id')
                            ->label('所屬學校')
                            ->relationship('school', 'name') // 使用 singular 'school'
                            ->required()
                            ->preload()
                            ->searchable()
                            ->placeholder('請選擇該員隸屬的學校...'),
                    ]),
            ]);
    }
}
