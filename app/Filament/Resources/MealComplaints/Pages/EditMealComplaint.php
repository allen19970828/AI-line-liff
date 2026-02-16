<?php

namespace App\Filament\Resources\MealComplaints\Pages;

use App\Filament\Resources\MealComplaints\MealComplaintResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditMealComplaint extends EditRecord
{
    protected static string $resource = MealComplaintResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
