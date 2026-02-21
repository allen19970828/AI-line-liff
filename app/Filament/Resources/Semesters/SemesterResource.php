<?php

namespace App\Filament\Resources\Semesters;

use App\Filament\Resources\Semesters\Pages\CreateSemester;
use App\Filament\Resources\Semesters\Pages\EditSemester;
use App\Filament\Resources\Semesters\Pages\ListSemesters;
use App\Filament\Resources\Semesters\Schemas\SemesterForm;
use App\Filament\Resources\Semesters\Tables\SemestersTable;
use App\Models\Semester;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class SemesterResource extends Resource
{
    protected static ?string $model = Semester::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static ?string $navigationLabel = '學期資料管理';

    public static function getModelLabel(): string
    {
        return '學期';
    }

    public static function getPluralModelLabel(): string
    {
        return '學期清單';
    }

    protected static string|\UnitEnum|null $navigationGroup = '校園午餐管理';

    protected static ?int $navigationSort = 3;

    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Schema $schema): Schema
    {
        return SemesterForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return SemestersTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            RelationManagers\ExceptionsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListSemesters::route('/'),
            'create' => CreateSemester::route('/create'),
            'activities' => Pages\ListSemesterActivities::route('/{record}/activities'),
            'edit' => EditSemester::route('/{record}/edit'),
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
