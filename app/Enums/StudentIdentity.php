<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

// ☯️ 數位靈魂：對應 PostgreSQL 的 smallint/tinyInteger
enum StudentIdentity: int implements HasColor, HasLabel
{
    case SELF_PAY = 1;   // 自費
    case INDIGENOUS = 2; // 原民
    case POVERTY = 3;    // 貧困
    case SAVINGS = 4;    // 教儲戶
    case DONATION = 5;   // 民間捐(補)助款

    /**
     * ☯️ 標準法規：Filament 官方法典要求的 getLabel
     */
    public function getLabel(): ?string
    {
        return match ($this) {
            self::SELF_PAY => '自費',
            self::INDIGENOUS => '原民',
            self::POVERTY => '貧困',
            self::SAVINGS => '教儲戶',
            self::DONATION => '民間捐(補)助款',
        };
    }

    /**
     * ☯️ 標準法規：Filament 官方法典要求的 getColor
     */
    public function getColor(): string|array|null
    {
        return match ($this) {
            self::SELF_PAY => 'gray',
            self::INDIGENOUS => 'orange',
            self::POVERTY => 'danger',
            self::SAVINGS => 'info',
            self::DONATION => 'success',
        };
    }

    /**
     * ☯️ 轉接頭一：解決 Resource 呼叫 ->label() 的報錯
     */
    public function label(): ?string
    {
        return $this->getLabel();
    }

    /**
     * ☯️ 轉接頭二：解決 Resource 呼叫 ->color() 的報錯
     */
    public function color(): string|array|null
    {
        return $this->getColor();
    }
}
