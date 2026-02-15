<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum StudentStatus: string implements HasColor, HasLabel
{
    case Active = 'active';           // 在學
    case Graduated = 'graduated';     // 畢業
    case TransferredOut = 'transfer'; // 轉出
    case Suspension = 'suspension';   // 休學

    public function getLabel(): ?string
    {
        return match ($this) {
            self::Active => '在學',
            self::Graduated => '畢業',
            self::TransferredOut => '轉出',
            self::Suspension => '休學',
        };
    }

    public function getColor(): string|array|null
    {
        return match ($this) {
            self::Active => 'success',
            self::Graduated => 'gray',
            self::TransferredOut => 'warning',
            self::Suspension => 'danger',
        };
    }

    /**
     * ☯️ 轉接頭：解決 Resource 呼叫 ->label() 的報錯
     */
    public function label(): ?string
    {
        return $this->getLabel();
    }

    /**
     * ☯️ 轉接頭：解決 Resource 呼叫 ->color() 的報錯
     */
    public function color(): string|array|null
    {
        return $this->getColor();
    }
}
