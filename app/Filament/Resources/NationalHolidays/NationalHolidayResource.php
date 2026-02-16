<?php

namespace App\Filament\Resources\NationalHolidays;

use App\Filament\Resources\NationalHolidays\Schemas\NationalHolidayForm; // 引入劍刃
use App\Filament\Resources\NationalHolidays\Tables\NationalHolidaysTable; // 引入劍脊
use App\Models\NationalHoliday;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use UnitEnum;

class NationalHolidayResource extends Resource
{
    protected static ?string $model = NationalHoliday::class;

    // 宗師建議：換個莊嚴的圖示，並歸類在系統區
    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-globe-asia-australia';

    protected static ?string $navigationLabel = '國定假日總表';

    protected static ?string $modelLabel = '國定假日';

    protected static string|UnitEnum|null $navigationGroup = '系統管理';

    protected static ?int $navigationSort = 99; // 放後面一點，因為這是一年才動一次的東西

    public static function form(Schema $schema): Schema
    {
        // 宗師指點：一劍封喉
        return NationalHolidayForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        // 宗師指點：一招制敵
        return NationalHolidaysTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListNationalHolidays::route('/'),
            'create' => Pages\CreateNationalHoliday::route('/create'),
            'activities' => Pages\ListNationalHolidayActivities::route('/{record}/activities'),
            'edit' => Pages\EditNationalHoliday::route('/{record}/edit'),
        ];
    }
}
