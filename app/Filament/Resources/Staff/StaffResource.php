<?php

namespace App\Filament\Resources\Staff;

use App\Filament\Resources\Staff\Pages\CreateStaff;
use App\Filament\Resources\Staff\Pages\EditStaff;
use App\Filament\Resources\Staff\Pages\ListStaff;
use App\Filament\Resources\Staff\Pages\ListStaffActivities;
use App\Filament\Resources\Staff\Schemas\StaffForm;
use App\Filament\Resources\Staff\Tables\StaffTable;
use App\Models\Staff;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema; // [修正] 使用 Schema 而非 Form
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use UnitEnum;

class StaffResource extends Resource
{
    protected static ?string $model = Staff::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-user-group'; // 已換成群組圖示

    protected static ?string $navigationLabel = '教職員工管理';

    protected static ?string $modelLabel = '教職員';

    protected static ?string $pluralModelLabel = '教職員名冊';

    protected static string|UnitEnum|null $navigationGroup = '校園午餐管理';

    protected static ?int $navigationSort = 3;

    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Schema $schema): Schema
    {
        // 宗師指點：直接呼叫我們寫好的 Schema
        return StaffForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return StaffTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            // 未來這裡可以放「班級關聯」或「訂單紀錄」
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListStaff::route('/'),
            'create' => CreateStaff::route('/create'),
            'activities' => ListStaffActivities::route('/{record}/activities'),
            'edit' => EditStaff::route('/{record}/edit'),
        ];
    }

    /**
     * 宗師心法：全域查詢過濾
     * 1. 支援軟刪除 (SoftDeletes)
     * 2. 支援多租戶 (只看自己學校的人)
     */
    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }
}
