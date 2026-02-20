<?php

namespace App\Filament\Resources\LunchSettlements;

use App\Filament\Resources\LunchSettlements\Pages\CreateLunchSettlement;
use App\Filament\Resources\LunchSettlements\Pages\EditLunchSettlement;
use App\Filament\Resources\LunchSettlements\Pages\ListLunchSettlements;
use App\Filament\Resources\LunchSettlements\Schemas\LunchSettlementForm;
use App\Filament\Resources\LunchSettlements\Tables\LunchSettlementsTable;
use App\Models\LunchSettlement;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class LunchSettlementResource extends Resource
{
    protected static ?string $model = LunchSettlement::class;

    protected static ?string $navigationLabel = '午餐月結單';

    protected static ?string $pluralLabel = '午餐月結單';

    protected static ?string $modelLabel = '午餐月結單';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    public static function form(Schema $schema): Schema
    {
        return LunchSettlementForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return LunchSettlementsTable::configure($table);
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
            'index' => ListLunchSettlements::route('/'),
            'create' => CreateLunchSettlement::route('/create'),
            'edit' => EditLunchSettlement::route('/{record}/edit'),
        ];
    }
}
