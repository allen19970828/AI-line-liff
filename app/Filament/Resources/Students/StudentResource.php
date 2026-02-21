<?php

namespace App\Filament\Resources\Students;

use App\Filament\Resources\Students\RelationManagers\StatusHistoriesRelationManager;
use App\Filament\Resources\Students\Schemas\StudentForm;
use App\Filament\Resources\Students\Tables\StudentsTable;
use App\Models\Student;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Enums\Width;
use Filament\Tables\Table;
use UnitEnum;

class StudentResource extends Resource
{
    protected static ?string $model = Student::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-user-group';

    protected static ?string $navigationLabel = '學生資料管理';

    public static function getModelLabel(): string
    {
        return '學生';
    }

    public static function getPluralModelLabel(): string
    {
        return '學生清單';
    }

    protected static string|UnitEnum|null $navigationGroup = '學籍與補助';

    protected static ?int $navigationSort = 1;

    public static function form(Schema $schema): Schema
    {
        return StudentForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns(StudentsTable::columns())
            ->filters(StudentsTable::filters())
            ->actions([
                EditAction::make(),
                Action::make('activities')
                    ->label('異動紀錄')
                    ->icon('heroicon-m-clock')
                    ->color('info')
                    ->url(fn ($record) => static::getUrl('activities', ['record' => $record])),
            ])
            ->bulkActions([DeleteBulkAction::make()]);
    }

    public static function getRelations(): array
    {
        return [
            // 宗師指點：在這裡掛載歷程管理器
            StatusHistoriesRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListStudents::route('/'),
            'create' => Pages\CreateStudent::route('/create'),
            'edit' => Pages\EditStudent::route('/{record}/edit'),
            'activities' => Pages\ListStudentActivities::route('/{record}/activities'),
        ];
    }

    /**
     * [宗師奧義] 強制全寬模式
     * 這會讓 列表(List)、建立(Create)、編輯(Edit) 全部變成滿版
     */
    public static function getMaxContentWidth(): Width
    {
        return Width::Full;
    }

    // 多租戶過濾...
}
