<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum SemesterStage: string implements HasColor, HasLabel
{
    case Elementary = 'elementary';
    case Kindergarten = 'kindergarten';
    case Junior = 'junior';

    public function getLabel(): ?string
    {
        return match ($this) {
            self::Elementary => '國小部',
            self::Kindergarten => '附設幼兒園',
            self::Junior => '國中部',
        };
    }

    public function getColor(): string|array|null
    {
        return match ($this) {
            self::Elementary => 'info',
            self::Kindergarten => 'warning',
            self::Junior => 'success',
        };
    }
}
