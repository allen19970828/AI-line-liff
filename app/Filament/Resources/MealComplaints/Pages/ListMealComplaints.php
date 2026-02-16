<?php

namespace App\Filament\Resources\MealComplaints\Pages;

use App\Filament\Resources\MealComplaints\MealComplaintResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListMealComplaints extends ListRecords
{
    protected static string $resource = MealComplaintResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
