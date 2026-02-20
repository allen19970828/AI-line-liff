<?php

namespace App\Filament\Resources\LunchSettlements\Pages;

use App\Filament\Resources\LunchSettlements\LunchSettlementResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditLunchSettlement extends EditRecord
{
    protected static string $resource = LunchSettlementResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
