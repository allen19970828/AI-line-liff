<?php

namespace App\Filament\Resources\Surveys\RelationManagers;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TagsInput;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;

class QuestionsRelationManager extends RelationManager
{
    protected static string $relationship = 'questions';

    protected static ?string $title = '問卷題目';

    protected static ?string $modelLabel = '題目';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('label')
                    ->label('題目')
                    ->required()
                    ->maxLength(255)
                    ->columnSpanFull(),

                Select::make('type')
                    ->label('類型')
                    ->options([
                        'rating_5' => '5星評分',
                        'rating_10' => '10星評分',
                        'text' => '簡答題',
                        'textarea' => '詳答題',
                        'select' => '下拉選單',
                        'checkbox' => '多選題',
                        'radio' => '單選題',
                        'photo' => '照片上傳', // 自定義功能
                    ])
                    ->default('rating_5')
                    ->required()
                    ->reactive(),

                TagsInput::make('options')
                    ->label('選項 (輸入後按 Enter)')
                    ->placeholder('例如: 選項A, 選項B')
                    ->visible(fn (Get $get) => in_array($get('type'), ['select', 'checkbox', 'radio']))
                    ->columnSpanFull(),

                TextInput::make('analysis_code')
                    ->label('分析代碼 (AI 用)')
                    ->placeholder('例如: main_dish_taste'),

                TextInput::make('sort_order')
                    ->label('排序')
                    ->numeric()
                    ->default(0),

                Toggle::make('is_required')
                    ->label('必填')
                    ->default(true),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('label')
            ->reorderable('sort_order')
            ->defaultSort('sort_order', 'asc')
            ->columns([
                Tables\Columns\TextColumn::make('sort_order')
                    ->label('排序')
                    ->sortable(),

                Tables\Columns\TextColumn::make('label')
                    ->label('題目')
                    ->searchable(),

                Tables\Columns\TextColumn::make('type')
                    ->label('類型')
                    ->badge(),

                Tables\Columns\TextColumn::make('analysis_code')
                    ->label('分析代碼')
                    ->color('gray')
                    ->copyable(),

                Tables\Columns\IconColumn::make('is_required')
                    ->label('必填')
                    ->boolean(),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                CreateAction::make(),
            ])
            ->actions([
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
