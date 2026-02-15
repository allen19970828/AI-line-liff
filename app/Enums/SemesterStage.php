<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;

enum SemesterStage: string implements HasColor, HasIcon, HasLabel
{
    case JuniorHigh = 'junior_high';    // 國中
    case HighSchool = 'high_school';    // 高中 (預留給未來擴充)

    case Elementary = 'elementary';   // 國小部 (預設)
    case Kindergarten = 'kindergarten'; // 幼兒園 (附幼) - 經費來源不同

    public function getLabel(): ?string
    {
        return match ($this) {
            self::JuniorHigh => '國中',
            self::HighSchool => '高中',
            self::Elementary => '國小部',
            self::Kindergarten => '幼兒園 (附幼)',
        };
    }

    public function getColor(): string|array|null
    {
        return match ($this) {
            self::JuniorHigh => 'info',
            self::HighSchool => 'warning',
            self::Elementary => 'success',
            self::Kindergarten => 'danger',
        };
    }

    public function getIcon(): ?string
    {
        return match ($this) {
            self::JuniorHigh => 'heroicon-o-academic-cap',
            self::HighSchool => 'heroicon-o-academic-cap',
            self::Elementary => 'heroicon-o-academic-cap',
            self::Kindergarten => 'heroicon-o-face-smile',
        };
    }
}
