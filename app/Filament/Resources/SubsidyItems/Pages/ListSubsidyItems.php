<?php

namespace App\Filament\Resources\SubsidyItems\Pages;

use App\Filament\Resources\SubsidyItems\SubsidyItemResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListSubsidyItems extends ListRecords
{
    protected static string $resource = SubsidyItemResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
