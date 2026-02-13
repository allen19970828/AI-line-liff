<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum StaffMealMode: string implements HasLabel
{
    case Daily = 'daily';
    case Monthly = 'monthly';

    public function getLabel(): ?string
    {
        return match ($this) {
            self::Daily => '按日扣款',
            self::Monthly => '按月結算',
        };
    }
}
