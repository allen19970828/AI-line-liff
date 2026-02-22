<?php

namespace App\Filament\Resources\Surveys;

use App\Enums\GradeLevel;
use App\Filament\Resources\Surveys\Pages\CreateSurvey;
use App\Filament\Resources\Surveys\Pages\EditSurvey;
use App\Filament\Resources\Surveys\Pages\ListSurveys;
use App\Filament\Resources\Surveys\RelationManagers\QuestionsRelationManager;
use App\Filament\Resources\Surveys\RelationManagers\ResponsesRelationManager;
use App\Models\Survey;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class SurveyResource extends Resource
{
    protected static ?string $model = Survey::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-clipboard-document-list';

    protected static string|\UnitEnum|null $navigationGroup = '問卷管理';

    protected static ?string $modelLabel = '問卷';

    public static function getPluralModelLabel(): string
    {
        return '問卷清單';
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Group::make()
                    ->schema([
                        Section::make('基本資訊')
                            ->schema([
                                TextInput::make('title')
                                    ->label('問卷標題')
                                    ->required()
                                    ->maxLength(255),

                                Textarea::make('description')
                                    ->label('問卷說明')
                                    ->rows(3)
                                    ->columnSpanFull(),

                                Select::make('semester_id')
                                    ->label('學期')
                                    ->relationship('semester', 'name')
                                    ->searchable()
                                    ->preload(),

                                DatePicker::make('period_month')
                                    ->label('歸屬月份')
                                    ->native(false)
                                    ->displayFormat('Y-m'),
                            ])->columns(2),

                        Section::make('發佈設定')
                            ->schema([
                                Select::make('status')
                                    ->label('狀態')
                                    ->options([
                                        'draft' => '草稿',
                                        'active' => '進行中',
                                        'closed' => '已結束',
                                    ])
                                    ->default('draft')
                                    ->required()
                                    ->native(false),

                                DateTimePicker::make('start_at')
                                    ->label('開始時間')
                                    ->native(false),

                                DateTimePicker::make('end_at')
                                    ->label('結束時間')
                                    ->native(false),
                            ])->columns(3),
                    ])->columnSpan(['lg' => 2]),

                Group::make()
                    ->schema([
                        Section::make('受眾設定')
                            ->schema([
                                CheckboxList::make('target_roles')
                                    ->label('目標角色')
                                    ->options([
                                        'student' => '學生',
                                        'staff' => '教職員',
                                        'parent' => '家長',
                                    ])
                                    ->columns(2),

                                Select::make('target_grade_levels')
                                    ->label('指定年級')
                                    ->multiple()
                                    ->options(GradeLevel::class),

                                // TODO: 班級選擇器 (可以之後再加)
                            ]),
                    ])->columnSpan(['lg' => 1]),
            ])->columns(3);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('title')
                    ->label('標題')
                    ->searchable(),

                Tables\Columns\TextColumn::make('status')
                    ->label('狀態')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'draft' => 'gray',
                        'active' => 'success',
                        'closed' => 'danger',
                    }),

                Tables\Columns\TextColumn::make('period_month')
                    ->label('月份')
                    ->date('Y-m')
                    ->sortable(),

                Tables\Columns\TextColumn::make('responses_count')
                    ->label('填寫數')
                    ->counts('responses')
                    ->badge(),

                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->actions([
                EditAction::make(),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            QuestionsRelationManager::class,
            ResponsesRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListSurveys::route('/'),
            'create' => CreateSurvey::route('/create'),
            'edit' => EditSurvey::route('/{record}/edit'),
        ];
    }

    public static function getRecordRouteBindingEloquentQuery(): Builder
    {
        return parent::getRecordRouteBindingEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }
}
