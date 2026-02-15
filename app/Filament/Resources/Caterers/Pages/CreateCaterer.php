<?php

namespace App\Filament\Resources\Caterers\Pages;

use App\Filament\Resources\Caterers\CatererResource;
use Filament\Resources\Pages\CreateRecord;
use Filament\Support\Enums\Width;

class CreateCaterer extends CreateRecord
{
    protected static string $resource = CatererResource::class;

    public function getMaxContentWidth(): Width|string|null
    {
        return Width::Full;
    }
}
