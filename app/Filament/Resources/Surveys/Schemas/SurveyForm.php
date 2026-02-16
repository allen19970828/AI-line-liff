<?php

namespace App\Filament\Resources\Surveys\Schemas;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class SurveyForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('school_id')
                    ->relationship('school', 'name')
                    ->required(),
                Select::make('semester_id')
                    ->relationship('semester', 'name'),
                TextInput::make('title')
                    ->required(),
                Textarea::make('description')
                    ->columnSpanFull(),
                DatePicker::make('period_month'),
                TextInput::make('target_roles'),
                TextInput::make('status')
                    ->required()
                    ->default('draft'),
                DateTimePicker::make('start_at'),
                DateTimePicker::make('end_at'),
                TextInput::make('target_grade_levels'),
                TextInput::make('target_class_ids'),
            ]);
    }
}
