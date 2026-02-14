<?php

namespace App\Filament\Resources\Semesters\RelationManagers;

use App\Models\SemesterException;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TagsInput;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Validation\Rules\Unique;

class ExceptionsRelationManager extends RelationManager
{
    protected static string $relationship = 'exceptions';

    // 宗師指點：設定一個好懂的標題與圖示
    protected static ?string $title = '行事曆例外 / 補課設定';

    protected static string|BackedEnum|null $icon = 'heroicon-m-calendar-days';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Grid::make(2)->schema([
                    // [1. 日期]
                    DatePicker::make('date')
                        ->label('日期')
                        ->required()
                        ->native(false)
                        ->displayFormat('Y-m-d')
                        // 宗師指點：確保同一學期內，日期不重複
                        ->unique(
                            table: 'semester_exceptions',
                            column: 'date',
                            ignoreRecord: true,
                            modifyRuleUsing: function (Unique $rule, RelationManager $livewire) {
                                // 鎖定範圍：只能檢查「當前學期」的日期
                                return $rule->where('semester_id', $livewire->getOwnerRecord()->id);
                            }
                        ),

                    // [2. 類型]
                    Select::make('type')
                        ->label('類型')
                        ->options([
                            SemesterException::TYPE_HOLIDAY => '放假 (Holiday)',
                            SemesterException::TYPE_MAKEUP => '補課 (Makeup)',
                            SemesterException::TYPE_ACTIVITY => '活動 (Activity)',
                            SemesterException::TYPE_TYPHOON => '天災 (Typhoon)',
                            SemesterException::TYPE_OTHER => '其他 (Other)',
                        ])
                        ->required()
                        ->native(false)
                        ->live() // 開啟連動模式
                        ->afterStateUpdated(function (Get $get, Set $set, ?string $state) {
                            // 宗師自動化：選補課自動開供餐，選放假自動關供餐
                            if ($state == SemesterException::TYPE_MAKEUP) {
                                $set('is_meal_day', true);
                            } elseif ($state == SemesterException::TYPE_HOLIDAY || $state == SemesterException::TYPE_TYPHOON) {
                                $set('is_meal_day', false);
                            }
                        }),
                ]),

                Grid::make(2)->schema([
                    // [3. 供餐開關]
                    Toggle::make('is_meal_day')
                        ->label('當日是否供餐')
                        ->onColor('success')
                        ->offColor('danger')
                        ->default(false),

                    // [4. 顯示於報表]
                    Toggle::make('show_in_reports')
                        ->label('顯示於家長報表')
                        ->default(true),
                ]),

                // [5. 適用年級 (JSONB 神技)]
                // 使用 TagsInput 來處理 JSON 陣列，使用者輸入 "6" 按 Enter 即可
                TagsInput::make('target_grades')
                    ->label('適用年級 (留空代表全校適用)')
                    ->placeholder('輸入年級並按 Enter (ex: 6)')
                    ->helperText('若只有特定年級受影響才填寫，例如畢業旅行。')
                    ->columnSpanFull(),

                // [6. 說明]
                TextInput::make('description')
                    ->label('說明 / 備註')
                    ->placeholder('ex: 校慶補假')
                    ->maxLength(255)
                    ->columnSpanFull(),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('date')
            ->columns([
                TextColumn::make('date')
                    ->label('日期')
                    ->date('Y-m-d')
                    ->sortable()
                    ->weight('bold'),

                // 宗師指點：用 Badge 顯示類型，並自動上色
                TextColumn::make('type')
                    ->label('類型')
                    ->badge()
                    ->formatStateUsing(fn (SemesterException $record) => $record->getTypeName())
                    ->color(fn (SemesterException $record) => $record->getColor()),

                // 用圖示顯示供餐狀態，直觀
                IconColumn::make('is_meal_day')
                    ->label('供餐')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle'),

                TextColumn::make('description')
                    ->label('說明')
                    ->limit(20),

                // 顯示適用年級
                TextColumn::make('target_grades')
                    ->label('適用年級')
                    ->badge()
                    ->separator(',')
                    ->placeholder('全校'),
            ])
            ->filters([
                // 過濾器：只想看補課日或放假日
                SelectFilter::make('type')
                    ->label('類型')
                    ->options([
                        SemesterException::TYPE_HOLIDAY => '放假',
                        SemesterException::TYPE_MAKEUP => '補課',
                    ]),
            ])
            ->headerActions([
                CreateAction::make()
                    ->label('新增例外日'),
            ])
            ->actions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('date', 'asc'); // 依照日期排序
    }
}
