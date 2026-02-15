<?php

namespace App\Filament\Resources\LineAccounts\Pages;

use App\Filament\Resources\LineAccountResource;
use Filament\Resources\Pages\ListRecords;

class ListLineAccounts extends ListRecords
{
    protected static string $resource = LineAccountResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // No Create Action needed as accounts are created via OAuth
        ];
    }
}
