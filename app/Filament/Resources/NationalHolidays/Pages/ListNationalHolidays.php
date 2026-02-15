<?php

namespace App\Filament\Resources\NationalHolidays\Pages;

use App\Filament\Resources\NationalHolidays\NationalHolidayResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListNationalHolidays extends ListRecords
{
    protected static string $resource = NationalHolidayResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }

    protected function getHeaderWidgets(): array
    {
        return [
            \App\Filament\Resources\NationalHolidays\Widgets\SyncNationalHolidaysWidget::class,
        ];
    }
}
