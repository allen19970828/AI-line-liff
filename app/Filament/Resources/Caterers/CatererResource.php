<?php

namespace App\Filament\Resources\Caterers;

use App\Filament\Resources\Caterers\Pages\CreateCaterer;
use App\Filament\Resources\Caterers\Pages\EditCaterer;
use App\Filament\Resources\Caterers\Pages\ListCaterers;
use App\Filament\Resources\Caterers\Schemas\CatererForm;
use App\Filament\Resources\Caterers\Tables\CaterersTable;
use App\Models\Caterer;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use UnitEnum;

class CatererResource extends Resource
{
    protected static ?string $model = Caterer::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-truck'; // 圖示：卡車 (運送午餐)

    protected static ?string $navigationLabel = '合作廠商管理';

    protected static ?string $modelLabel = '廠商';

    protected static ?string $pluralModelLabel = '廠商列表';

    protected static string|UnitEnum|null $navigationGroup = '校園午餐管理';

    protected static ?int $navigationSort = 2; // 排在 Semester 之後

    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Schema $schema): Schema
    {
        // 宗師指點：看，多麼乾淨！
        return CatererForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        // 宗師指點：邏輯完全分離，維護輕鬆
        return CaterersTable::configure($table);
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
            'index' => ListCaterers::route('/'),
            'create' => CreateCaterer::route('/create'),
            'edit' => EditCaterer::route('/{record}/edit'),
        ];
    }

    /**
     * 宗師心法：多租戶資料隔離 (Scope)
     * 確保使用者只能看到自己學校的廠商
     */
    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->where('school_id', auth()->user()->school?->id);
    }

    public static function getRecordRouteBindingEloquentQuery(): Builder
    {
        return parent::getRecordRouteBindingEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }
}
