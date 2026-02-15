<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum DietaryType: string implements HasColor, HasLabel
{
    case Meat = 'meat';
    case Vegan = 'vegan'; // 全素
    case OvoLacto = 'ovo_lacto'; // 蛋奶素
    case Vegetarian = 'vegetarian'; // 方便素

    public function getLabel(): ?string
    {
        return match ($this) {
            self::Meat => '葷食',
            self::Vegan => '全素',
            self::Vegetarian => '素食',
            self::OvoLacto => '蛋奶素',
        };
    }

    public function getColor(): string|array|null
    {
        return match ($this) {
            self::Meat => 'gray',
            self::Vegan, self::Vegetarian, self::OvoLacto => 'success',
        };
    }
}
