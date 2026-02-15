<?php

namespace App\Filament\Resources\Semesters\Pages;

use App\Filament\Resources\Semesters\SemesterResource;
use Filament\Resources\Pages\CreateRecord;

class CreateSemester extends CreateRecord
{
    protected static string $resource = SemesterResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // [宗師修正] 自動綁定目前登入者的單一學校
        $data['school_id'] = auth()->user()->school?->id;

        return $data;
    }
}
