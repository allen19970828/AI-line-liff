<?php

namespace App\Filament\Resources\Schools\Schemas;

use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class SchoolForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                // 左側：基本資料 (2/3)
                Group::make()
                    ->schema([
                        Section::make('基本資料')
                            ->icon('heroicon-m-identification')
                            ->description('請輸入學校的核心識別與營運資訊')
                            ->schema([
                                Grid::make(2)
                                    ->schema([
                                        TextInput::make('code')
                                            ->label('學校代碼')
                                            ->required()
                                            ->unique(ignoreRecord: true) // 注入防禦
                                            ->maxLength(20)
                                            ->placeholder('例如：123456'),

                                        TextInput::make('name')
                                            ->label('學校全名')
                                            ->required()
                                            ->maxLength(255)
                                            ->placeholder('例如：臺北市立建國高級中學'),
                                    ]),

                                Grid::make(2)
                                    ->schema([
                                        TextInput::make('tax_id')
                                            ->label('統一編號')
                                            ->maxLength(20)
                                            ->placeholder('若無可留空'),

                                        TextInput::make('phone')
                                            ->label('學校總機')
                                            ->tel()
                                            ->maxLength(20)
                                            ->prefixIcon('heroicon-m-phone'),
                                    ]),

                                TextInput::make('website')
                                    ->label('官方網站')
                                    ->url()
                                    ->maxLength(255)
                                    ->prefix('https://')
                                    ->suffixIcon('heroicon-m-globe-alt'),
                            ]),

                        Section::make('地理位置')
                            ->icon('heroicon-m-map-pin')
                            ->schema([
                                Grid::make(2)
                                    ->schema([
                                        TextInput::make('city')
                                            ->label('縣市')
                                            ->maxLength(50)
                                            ->placeholder('例如：臺北市'),

                                        TextInput::make('district')
                                            ->label('行政區')
                                            ->maxLength(50)
                                            ->placeholder('例如：中正區'),
                                    ]),

                                TextInput::make('address')
                                    ->label('詳細地址')
                                    ->maxLength(255)
                                    ->columnSpanFull(),
                            ]),
                    ])
                    ->columnSpan(['lg' => 2]),

                // 右側：狀態控制 (1/3)
                Group::make()
                    ->schema([
                        Section::make('狀態控制')
                            ->icon('heroicon-m-cog-6-tooth')
                            ->schema([
                                Toggle::make('is_active')
                                    ->label('啟用營運')
                                    ->helperText('啟用的學校才能進行後續操作')
                                    ->default(false)
                                    ->offIcon('heroicon-m-x-circle')
                                    ->offColor('danger')
                                    ->onIcon('heroicon-m-check-circle')
                                    ->onColor('success'),

                                TextInput::make('checkin_code')
                                    ->label('報到特徵碼')
                                    ->description('系統自動生成的唯一報到代碼，不可修改。')
                                    ->readOnly()
                                    ->prefixIcon('heroicon-m-qr-code')
                                    ->visibleOn('edit'),

                                Placeholder::make('created_at')
                                    ->label('建立時間')
                                    ->content(fn ($record): string => $record?->created_at?->diffForHumans() ?? '-'),

                                Placeholder::make('updated_at')
                                    ->label('最後更新')
                                    ->content(fn ($record): string => $record?->updated_at?->diffForHumans() ?? '-'),
                            ]),
                    ])
                    ->columnSpan(['lg' => 1]),
            ])
            ->columns(3);
    }
}
