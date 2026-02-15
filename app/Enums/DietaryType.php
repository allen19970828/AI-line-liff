<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum DietaryType: string implements HasColor, HasLabel
{
    case Meat = 'meat';  // 葷食
    case Vegan = 'veg';  // 素食 (全素)
    case Vegetarian = 'vegetarian'; // 素食 (蛋奶素/方便素)
    case OvoLactoVegetarian = 'ovo_lacto'; // 蛋奶素 (明確定義)

    public function getLabel(): ?string
    {
        return match ($this) {
            self::Meat => '葷食',
            self::Vegan => '全素',
            self::Vegetarian => '素食',
            self::OvoLactoVegetarian => '蛋奶素',
        };
    }

    public function getColor(): string|array|null
    {
        return match ($this) {
            self::Meat => 'gray',
            self::Vegan, self::Vegetarian, self::OvoLactoVegetarian => 'success',
        };
    }
}
