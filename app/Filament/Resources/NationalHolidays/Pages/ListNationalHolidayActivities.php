<?php

namespace App\Filament\Resources\NationalHolidays\Pages;

use App\Filament\Resources\NationalHolidays\NationalHolidayResource;
use pxlrbt\FilamentActivityLog\Pages\ListActivities;

class ListNationalHolidayActivities extends ListActivities
{
    protected static string $resource = NationalHolidayResource::class;
}
