<?php

namespace App\Enums\Meals;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum CancelLeaveType: string implements HasColor, HasLabel
{
    case Personal = 'personal'; // 事假
    case Sick = 'sick';         // 病假
    case Administrative = 'administrative'; // 公假
    case Arrears = 'arrears';   // 欠費停餐
    case Quit = 'quit';         // 退伙

    public function getLabel(): ?string
    {
        return match ($this) {
            self::Personal => '事假',
            self::Sick => '病假',
            self::Administrative => '公假',
            self::Arrears => '欠費停餐',
            self::Quit => '退伙',
        };
    }

    public function getColor(): string|array|null
    {
        return match ($this) {
            self::Personal, self::Administrative => 'info',
            self::Sick => 'warning',
            self::Arrears, self::Quit => 'danger',
        };
    }
}
