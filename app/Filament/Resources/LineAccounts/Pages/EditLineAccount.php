<?php

namespace App\Filament\Resources\LineAccounts\Pages;

use App\Filament\Resources\LineAccountResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditLineAccount extends EditRecord
{
    protected static string $resource = LineAccountResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
