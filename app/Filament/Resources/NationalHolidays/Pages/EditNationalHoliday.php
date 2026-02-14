<?php

namespace App\Filament\Resources\NationalHolidays\Pages;

use App\Filament\Resources\NationalHolidays\NationalHolidayResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditNationalHoliday extends EditRecord
{
    protected static string $resource = NationalHolidayResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
