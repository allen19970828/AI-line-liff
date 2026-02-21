<?php

namespace App\Filament\Resources;

use App\Filament\Resources\LineAccounts\Pages;
use App\Filament\Resources\LineAccounts\Schemas\LineAccountForm;
use App\Filament\Resources\LineAccounts\Tables\LineAccountsTable;
use App\Models\LineAccount;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;

class LineAccountResource extends Resource
{
    protected static ?string $model = LineAccount::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-chat-bubble-left-right';

    protected static ?string $navigationLabel = 'LINE 帳號管理';

    public static function getModelLabel(): string
    {
        return 'LINE 帳號';
    }

    public static function getPluralModelLabel(): string
    {
        return 'LINE 帳號名冊';
    }

    protected static string|\UnitEnum|null $navigationGroup = '系統設定';

    protected static ?int $navigationSort = 10;

    protected static ?string $recordTitleAttribute = 'display_name';

    public static function form(Schema $schema): Schema
    {
        return LineAccountForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return LineAccountsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            // No relations needed for now
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListLineAccounts::route('/'),
            'edit' => Pages\EditLineAccount::route('/{record}/edit'),
        ];
    }
}
