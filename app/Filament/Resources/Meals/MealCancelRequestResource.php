<?php

namespace App\Filament\Resources\Meals;

use App\Filament\Resources\Meals\Pages\CreateMealCancelRequest;
use App\Filament\Resources\Meals\Pages\EditMealCancelRequest;
use App\Filament\Resources\Meals\Pages\ListMealCancelRequests;
use App\Filament\Resources\Meals\Schemas\MealCancelRequestForm;
use App\Filament\Resources\Meals\Tables\MealCancelRequestsTable;
use App\Models\MealCancelRequest;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class MealCancelRequestResource extends Resource
{
    protected static ?string $model = MealCancelRequest::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-x-circle';

    protected static ?string $navigationLabel = '停餐申請';

    protected static string|\UnitEnum|null $navigationGroup = '校園午餐管理';

    protected static ?int $navigationSort = 4;

    protected static ?string $modelLabel = '停餐申請';

    protected static ?string $pluralModelLabel = '停餐申請';

    public static function form(Schema $schema): Schema
    {
        return MealCancelRequestForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return MealCancelRequestsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListMealCancelRequests::route('/'),
            'create' => CreateMealCancelRequest::route('/create'),
            'edit' => EditMealCancelRequest::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }
}
