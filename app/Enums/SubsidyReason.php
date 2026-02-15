<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum SubsidyReason: string implements HasLabel
{
    case LOW_INCOME = '1';
    case MIDDLE_LOW_INCOME = '2';
    case FAMILY_HARDSHIP = '3';
    case TEACHER_ASSIGNED = '4';

    public function getLabel(): ?string
    {
        return match ($this) {
            self::LOW_INCOME => '1.社會局或區公所列冊低收入戶（檢附證明）',
            self::MIDDLE_LOW_INCOME => '2.社會局或區公所列冊中低收入戶（檢附證明）',
            self::FAMILY_HARDSHIP => '3.家庭突遭變故以至經濟困頓（檢附相關證明）',
            self::TEACHER_ASSIGNED => '4.其他，導師認定爲經濟困難者',
        };
    }

    /**
     * ☯️ 轉接頭：解決 Resource 呼叫 ->label() 的報錯
     */
    public function label(): ?string
    {
        return $this->getLabel();
    }
}
