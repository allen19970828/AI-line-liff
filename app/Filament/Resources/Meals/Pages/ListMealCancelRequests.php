<?php

namespace App\Filament\Resources\Meals\Pages;

use App\Filament\Resources\Meals\MealCancelRequestResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListMealCancelRequests extends ListRecords
{
    protected static string $resource = MealCancelRequestResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
