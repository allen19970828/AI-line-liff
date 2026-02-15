<?php

namespace App\Filament\Resources\Students\Pages;

use App\Filament\Resources\Students\StudentResource;
use pxlrbt\FilamentActivityLog\Pages\ListActivities;

class ListStudentActivities extends ListActivities
{
    protected static string $resource = StudentResource::class;
}
