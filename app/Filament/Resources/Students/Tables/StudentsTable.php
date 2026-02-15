<?php

namespace App\Filament\Resources\Students\Tables;

use App\Enums\SemesterStage;
use App\Enums\StudentIdentity;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TrashedFilter;
use Illuminate\Database\Eloquent\Builder;

class StudentsTable
{
    public static function columns(): array
    {
        return [
            TextColumn::make('classroom.class_name')
                ->label('班級')
                ->sortable()
                ->searchable(),

            TextColumn::make('seat_number')
                ->label('座號')
                ->sortable(),

            TextColumn::make('name')
                ->label('姓名')
                ->searchable()
                ->weight('bold'),

            // 顯示年級 (透過 Accessor)
            TextColumn::make('grade_level')
                ->label('年級')
                ->toggleable(isToggledHiddenByDefault: true),

            TextColumn::make('identity')
                ->label('身分')
                ->badge()
                ->sortable(),

            TextColumn::make('status')
                ->label('狀態')
                ->badge()
                ->sortable(),

            TextColumn::make('dietary_type')
                ->label('飲食')
                ->badge(),

            IconColumn::make('is_meal_user')
                ->label('用餐')
                ->boolean(),
        ];
    }

    public static function filters(): array
    {
        return [
            TrashedFilter::make(),

            SelectFilter::make('classroom_id')
                ->label('班級')
                ->relationship('classroom', 'class_name', fn (Builder $query) => $query->where('school_id', auth()->user()->school?->id))
                ->searchable()
                ->preload(),

            SelectFilter::make('identity')
                ->label('身分')
                ->options(StudentIdentity::class),

            SelectFilter::make('stage')
                ->label('部別')
                ->options(SemesterStage::class),
        ];
    }
}
