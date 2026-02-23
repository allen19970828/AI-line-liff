<?php

namespace App\Filament\Resources\SubsidyItems\Schemas;

use App\Enums\SubsidySource;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TagsInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\ToggleButtons;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Builder;

class SubsidyItemForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('基本資料')
                    ->description('設定補貼項目的基本屬性。')
                    ->schema([
                        Select::make('school_id')
                            ->relationship('school', 'name')
                            ->label('所屬學校')
                            ->default(fn () => auth()->user()?->school_id)
                            ->hidden(fn () => ! auth()->user()?->canBypassSchoolScope())
                            ->required(),
                        Select::make('semester_id')
                            ->label('適用學期')
                            ->relationship('semester', 'name', fn (Builder $query) => $query->where('school_id', auth()->user()?->school_id))
                            ->default(fn () => \App\Models\Semester::where('school_id', auth()->user()?->school_id)->where('is_active', true)->value('id'))
                            ->required()
                            ->columnSpanFull(),
                        TextInput::make('name')
                            ->label('補貼名稱')
                            ->placeholder('例如：原住民補助')
                            ->required()
                            ->maxLength(255),
                        TextInput::make('code')
                            ->label('系統代碼')
                            ->placeholder('例如：indigenous_subsidy')
                            ->maxLength(255),
                        Textarea::make('description')
                            ->label('補充說明')
                            ->columnSpanFull(),
                    ])->columns(2),

                Section::make('適用對象與金額')
                    ->description('設定補助對象條件及每餐金額上限。')
                    ->schema([
                        TagsInput::make('target_roles')
                            ->label('適用身份角色')
                            ->placeholder('輸入後按 Enter (例: student)')
                            ->separator(',')
                            ->columnSpan(1),
                        TagsInput::make('identity_tags')
                            ->label('具備身份標籤')
                            ->placeholder('輸入後按 Enter (例: low_income)')
                            ->separator(',')
                            ->columnSpan(1),
                        ToggleButtons::make('source_type')
                            ->label('補助來源')
                            ->options(SubsidySource::class)
                            ->default(SubsidySource::LOCAL)
                            ->inline()
                            ->required()
                            ->columnSpanFull(),
                        Toggle::make('is_full_coverage')
                            ->label('是否全額補助')
                            ->helperText('若啟用，則代表該生免繳自付額。')
                            ->default(false)
                            ->live()
                            ->required(),
                        TextInput::make('amount_per_meal')
                            ->label('每餐補助金額')
                            ->required()
                            ->numeric()
                            ->prefix('NT$')
                            ->disabled(fn (\Filament\Schemas\Components\Utilities\Get $get): bool => $get('is_full_coverage'))
                            ->default(0),
                        TextInput::make('budget_limit')
                            ->label('總預算上限')
                            ->helperText('0 代表無上限')
                            ->required()
                            ->numeric()
                            ->prefix('NT$')
                            ->default(0),
                        TextInput::make('priority')
                            ->label('優先權重')
                            ->helperText('數字越大優先級越高，當多個補助條件符合時，優先套用權重最高者。')
                            ->required()
                            ->numeric()
                            ->default(10),
                    ])->columns(2),

                Section::make('狀態')
                    ->schema([
                        Toggle::make('is_active')
                            ->label('啟用狀態')
                            ->default(true)
                            ->required(),
                    ]),
            ]);
    }
}
