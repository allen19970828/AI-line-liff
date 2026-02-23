<?php

namespace App\Filament\Resources\SubsidyItems;

use App\Filament\Resources\SubsidyItems\Pages\CreateSubsidyItem;
use App\Filament\Resources\SubsidyItems\Pages\EditSubsidyItem;
use App\Filament\Resources\SubsidyItems\Pages\ListSubsidyItems;
use App\Filament\Resources\SubsidyItems\Schemas\SubsidyItemForm;
use App\Filament\Resources\SubsidyItems\Tables\SubsidyItemsTable;
use App\Models\SubsidyItem;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class SubsidyItemResource extends Resource
{
    protected static ?string $model = SubsidyItem::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-currency-dollar';

    protected static ?string $navigationLabel = '常規補貼設定';

    protected static ?string $modelLabel = '補貼項目';

    protected static ?string $pluralModelLabel = '補貼與身份設定';

    protected static string|\UnitEnum|null $navigationGroup = '校園午餐管理';

    protected static ?int $navigationSort = 4;

    public static function form(Schema $schema): Schema
    {
        return SubsidyItemForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return SubsidyItemsTable::configure($table);
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
            'index' => ListSubsidyItems::route('/'),
            'create' => CreateSubsidyItem::route('/create'),
            'edit' => EditSubsidyItem::route('/{record}/edit'),
        ];
    }

    public static function getRecordRouteBindingEloquentQuery(): Builder
    {
        return parent::getRecordRouteBindingEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }
}
