<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum SubsidySource: string implements HasColor, HasLabel
{
    case LOCAL = 'local';
    case GOVERNMENT = 'government';
    case DONATION = 'donation';

    public function getLabel(): ?string
    {
        return match ($this) {
            self::LOCAL => '校內/地方補助',
            self::GOVERNMENT => '中央/政府補助',
            self::DONATION => '民間捐助',
        };
    }

    public function getColor(): string|array|null
    {
        return match ($this) {
            self::LOCAL => 'info',
            self::GOVERNMENT => 'success',
            self::DONATION => 'warning',
        };
    }
}
