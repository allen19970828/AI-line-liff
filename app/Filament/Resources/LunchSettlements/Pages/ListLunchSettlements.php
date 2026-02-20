<?php

namespace App\Filament\Resources\LunchSettlements\Pages;

use App\Filament\Resources\LunchSettlements\LunchSettlementResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListLunchSettlements extends ListRecords
{
    protected static string $resource = LunchSettlementResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
