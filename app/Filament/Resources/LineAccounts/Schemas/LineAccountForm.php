<?php

namespace App\Filament\Resources\LineAccounts\Schemas;

use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class LineAccountForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                // === 左側主戰場 (佔 2/3) ===
                Group::make()
                    ->schema([
                        // [第一卷：識別資訊]
                        Section::make('帳號識別')
                            ->icon('heroicon-m-identification')
                            ->schema([
                                Grid::make(2)->schema([
                                    Select::make('user_id')
                                        ->label('綁定使用者')
                                        ->relationship('user', 'name')
                                        ->searchable()
                                        ->preload()
                                        ->required()
                                        ->columnSpan(1),

                                    TextInput::make('display_name')
                                        ->label('LINE 顯示名稱')
                                        ->required()
                                        ->maxLength(255)
                                        ->columnSpan(1),

                                    TextInput::make('line_user_id')
                                        ->label('LINE User ID (UID)')
                                        ->required()
                                        ->unique(ignoreRecord: true)
                                        ->maxLength(255)
                                        ->readOnly() // UID 通常不可變
                                        ->columnSpanFull(),
                                ]),
                            ]),

                        // [第二卷：金鑰密庫]
                        Section::make('Access Token 資訊')
                            ->icon('heroicon-m-key')
                            ->collapsible()
                            ->collapsed()
                            ->schema([
                                Textarea::make('access_token')
                                    ->label('Access Token')
                                    ->rows(3)
                                    ->readOnly(),

                                Textarea::make('refresh_token')
                                    ->label('Refresh Token')
                                    ->rows(2)
                                    ->readOnly(),

                                DateTimePicker::make('token_expires_at')
                                    ->label('Token 過期時間')
                                    ->readOnly(),
                            ]),
                    ])
                    ->columnSpan(['lg' => 2]),

                // === 右側側翼 (佔 1/3) ===
                Group::make()
                    ->schema([
                        Section::make('帳號狀態')
                            ->schema([
                                FileUpload::make('avatar_url')
                                    ->label('大頭貼預覽')
                                    ->image()
                                    ->avatar()
                                    ->imageEditor()
                                    ->circleCropper()
                                    ->directory('line-avatars') // 雖然通常是外部連結，但若要上傳則存這裡
                                    // 若 avatar_url 是外部連結，Filament 可能無法直接用 FileUpload 顯示
                                    // 這裡假設我們主要用於顯示，若要編輯可能需要特殊處理
                                    // 暫時用 TextInput + ImageColumn 在列表顯示比較保險，或是用 Placeholder
                                    ->hidden(),

                                TextInput::make('avatar_url')
                                    ->label('大頭貼 URL')
                                    ->readOnly(),

                                DateTimePicker::make('created_at')
                                    ->label('建立時間')
                                    ->visibleOn('view')
                                    ->readOnly(),

                                DateTimePicker::make('updated_at')
                                    ->label('更新時間')
                                    ->visibleOn('view')
                                    ->readOnly(),
                            ]),
                    ])
                    ->columnSpan(['lg' => 1]),
            ])
            ->columns(3);
    }
}
