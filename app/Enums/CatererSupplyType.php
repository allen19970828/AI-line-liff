<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum CatererSupplyType: string implements HasColor, HasLabel
{
    case PUBLIC_PUBLIC = 'public_public';   // 公辦公營
    case PUBLIC_PRIVATE = 'public_private'; // 公辦民營
    case OTHER_SCHOOL = 'other_school';     // 他校供應
    case CONTRACTED = 'contracted';         // 團膳/外訂
    case FOOD_COURT = 'food_court';         // 美食街/合作社

    public function getLabel(): ?string
    {
        return match ($this) {
            self::PUBLIC_PUBLIC => '公辦公營',
            self::PUBLIC_PRIVATE => '公辦民營',
            self::OTHER_SCHOOL => '他校供應',
            self::CONTRACTED => '團膳/外訂盒餐',
            self::FOOD_COURT => '美食街/合作社',
        };
    }

    public function getColor(): string|array|null
    {
        return match ($this) {
            self::PUBLIC_PUBLIC => 'success',
            self::PUBLIC_PRIVATE => 'info',
            self::OTHER_SCHOOL => 'warning',
            self::CONTRACTED => 'danger',
            self::FOOD_COURT => 'gray',
        };
    }

    /**
     * 是否允許並存 (Multi-Tenancy) - Rich Model Logic
     */
    public function allowsMultiple(): bool
    {
        return match ($this) {
            self::CONTRACTED, self::FOOD_COURT => true,
            default => false,
        };
    }
}
