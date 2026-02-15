<?php

namespace App\Filament\Resources\Caterers\Schemas;

use App\Enums\CatererSupplyType;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class CatererForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                // === 左側主資料 (佔 2/3) ===
                Group::make()
                    ->schema([
                        Section::make('廠商核心資料')
                            ->description('設定廠商名稱與供應類型')
                            ->icon('heroicon-m-building-storefront')
                            ->schema([
                                // 宗師補注：多租戶架構通常需要選學校，或是由系統自動帶入。
                                // 這裡維持原本的 school_id，並設為隱藏或必選，視專案習慣而定。
                                // 若專案已有全域 Tenant，則可移除此欄位。
                                // 宗師指點：自動綁定學校邏輯
                                // 若非超級管理員，則強制鎖定為當前使用者的 school_id
                                Select::make('school_id')
                                    ->relationship('school', 'name')
                                    ->label('所屬學校')
                                    ->required()
                                    ->searchable()
                                    ->preload()
                                    ->default(fn () => auth()->user()->school_id)
                                    ->disabled(fn () => ! auth()->user()->canBypassSchoolScope())
                                    ->dehydrated() // 即使 disabled 也要送出資料
                                    ->columnSpanFull(),

                                Grid::make(2)->schema([
                                    TextInput::make('name')
                                        ->label('廠商名稱')
                                        ->required()
                                        ->maxLength(255)
                                        ->placeholder('例如：快樂午餐有限公司'),

                                    Select::make('supply_type')
                                        ->label('供應類型')
                                        ->options(CatererSupplyType::class) // 宗師指點：直接用 Enum
                                        ->required()
                                        ->native(false), // 使用漂亮的 Filament 下拉選單

                                    TextInput::make('tax_id')
                                        ->label('統一編號')
                                        ->numeric()
                                        ->maxLength(8)
                                        ->minLength(8)
                                        ->placeholder('8碼統編'),
                                ]),
                            ]),
                    ])
                    ->columnSpan(['lg' => 2]),

                // === 右側側翼 (佔 1/3) ===
                Group::make()
                    ->schema([
                        Section::make('狀態與聯絡')
                            ->icon('heroicon-m-phone')
                            ->schema([
                                Toggle::make('is_active')
                                    ->label('是否啟用 (合格廠商)')
                                    ->default(true)
                                    ->onColor('success')
                                    ->offColor('danger')
                                    ->inline(false),

                                TextInput::make('contact_person')
                                    ->label('聯絡人')
                                    ->prefixIcon('heroicon-m-user'),

                                TextInput::make('phone')
                                    ->label('聯絡電話')
                                    ->tel()
                                    ->prefixIcon('heroicon-m-phone'),

                                TextInput::make('address')
                                    ->label('廠商地址')
                                    ->prefixIcon('heroicon-m-map-pin'),
                            ]),
                    ])
                    ->columnSpan(['lg' => 1]),
            ])
            ->columns(3);
    }
}
