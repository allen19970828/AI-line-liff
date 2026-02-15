<?php

namespace App\Enums\Meals;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum CancelRequestStatus: string implements HasColor, HasLabel
{
    case Pending = 'pending';
    case Approved = 'approved';
    case Rejected = 'rejected';

    public function getLabel(): ?string
    {
        return match ($this) {
            self::Pending => '待審核',
            self::Approved => '已核准',
            self::Rejected => '已駁回',
        };
    }

    public function getColor(): string|array|null
    {
        return match ($this) {
            self::Pending => 'warning',
            self::Approved => 'success',
            self::Rejected => 'danger',
        };
    }
}
