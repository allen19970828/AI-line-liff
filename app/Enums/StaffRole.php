<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum StaffRole: string implements HasColor, HasLabel
{
    case Teacher = 'teacher'; // 教師
    case Admin = 'admin';     // 職員
    case Worker = 'worker';   // 職工 (工友/警衛/廚工)

    public function getLabel(): ?string
    {
        return match ($this) {
            self::Teacher => '教師',
            self::Admin => '行政職員',
            self::Worker => '職工',
        };
    }

    public function getColor(): string|array|null
    {
        return match ($this) {
            self::Teacher => 'success', // 綠色
            self::Admin => 'info',      // 藍色
            self::Worker => 'gray',     // 灰色
        };
    }
}
