<?php

namespace App\Filament\Resources\MealComplaints;

use App\Filament\Resources\MealComplaints\Pages\CreateMealComplaint;
use App\Filament\Resources\MealComplaints\Pages\EditMealComplaint;
use App\Filament\Resources\MealComplaints\Pages\ListMealComplaints;
use App\Filament\Resources\MealComplaints\Schemas\MealComplaintForm;
use App\Filament\Resources\MealComplaints\Tables\MealComplaintsTable;
use App\Models\MealComplaint;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class MealComplaintResource extends Resource
{
    protected static ?string $model = MealComplaint::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static ?string $recordTitleAttribute = 'id';

    protected static ?string $modelLabel = '當日用餐問題反應';

    protected static ?string $pluralModelLabel = '當日用餐問題反應';

    protected static ?string $navigationLabel = '當日用餐問題反應';

    public static function form(Schema $schema): Schema
    {
        return MealComplaintForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return MealComplaintsTable::configure($table);
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
            'index' => ListMealComplaints::route('/'),
            'create' => CreateMealComplaint::route('/create'),
            'edit' => EditMealComplaint::route('/{record}/edit'),
        ];
    }
}
