<?php

namespace App\Filament\Resources\Surveys\RelationManagers;

use Filament\Actions\ViewAction;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;

class ResponsesRelationManager extends RelationManager
{
    protected static string $relationship = 'responses';

    protected static ?string $title = '填寫紀錄';

    protected static ?string $modelLabel = '填寫紀錄';

    public function infolist(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('填寫者資訊')
                    ->schema([
                        \Filament\Infolists\Components\TextEntry::make('snapshot_name')
                            ->label('填寫者'),

                        \Filament\Infolists\Components\TextEntry::make('snapshot_role')
                            ->label('角色')
                            ->formatStateUsing(fn (?string $state): string => match ($state) {
                                'staff' => '教職員',
                                'student' => '學生',
                                'parent' => '家長',
                                default => $state ?? '不明',
                            }),

                        \Filament\Infolists\Components\TextEntry::make('snapshot_class')
                            ->label('班級'),

                        \Filament\Infolists\Components\TextEntry::make('snapshot_grade')
                            ->label('年級'),

                        \Filament\Infolists\Components\TextEntry::make('created_at')
                            ->label('填寫時間')
                            ->dateTime('Y/m/d H:i'),
                    ])
                    ->columns(3),

                Section::make('回覆內容')
                    ->schema([
                        \Filament\Infolists\Components\RepeatableEntry::make('answers')
                            ->label('')
                            ->schema([
                                \Filament\Infolists\Components\TextEntry::make('question.label')
                                    ->label('題目'),

                                \Filament\Infolists\Components\TextEntry::make('rating_value')
                                    ->label('評分')
                                    ->placeholder('—'),

                                \Filament\Infolists\Components\TextEntry::make('text_value')
                                    ->label('文字回覆')
                                    ->placeholder('—'),
                            ])
                            ->columns(3)
                            ->columnSpanFull(),
                    ]),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('snapshot_name')
            ->defaultSort('created_at', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('snapshot_name')
                    ->label('填寫者')
                    ->searchable(),

                Tables\Columns\TextColumn::make('snapshot_role')
                    ->label('角色')
                    ->badge()
                    ->color(fn (?string $state): string => match ($state) {
                        'staff' => 'warning',
                        'student' => 'info',
                        'parent' => 'success',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (?string $state): string => match ($state) {
                        'staff' => '教職員',
                        'student' => '學生',
                        'parent' => '家長',
                        default => $state ?? '不明',
                    }),

                Tables\Columns\TextColumn::make('snapshot_class')
                    ->label('班級')
                    ->placeholder('—'),

                Tables\Columns\TextColumn::make('answers_count')
                    ->label('作答題數')
                    ->counts('answers')
                    ->badge(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('填寫時間')
                    ->dateTime('Y/m/d H:i')
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('snapshot_role')
                    ->label('角色')
                    ->options([
                        'staff' => '教職員',
                        'student' => '學生',
                        'parent' => '家長',
                    ]),
            ])
            ->actions([
                ViewAction::make()->label('查看詳情'),
            ])
            ->bulkActions([]);
    }
}
