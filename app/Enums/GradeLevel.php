<?php

namespace App\Enums;

// [Added]
use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel; // 建議加上顏色，Filament 列表會更漂亮

enum GradeLevel: int implements HasColor, HasLabel
{
    // --- 國小部 (1~6) ---
    case GRADE_1 = 1;
    case GRADE_2 = 2;
    case GRADE_3 = 3;
    case GRADE_4 = 4;
    case GRADE_5 = 5;
    case GRADE_6 = 6;

    // --- 幼兒園部 (11~13) ---
    // 宗師備註：使用 11, 12, 13 是為了避開 1~9 的排序混淆，這是好設計
    case KINDERGARTEN_SMALL = 11;  // 小班
    case KINDERGARTEN_MIDDLE = 12; // 中班
    case KINDERGARTEN_BIG = 13;    // 大班

    // [宗師新增] 畢業生專用區
    case GRADUATED = 99;

    public function getLabel(): string
    {
        return match ($this) {
            self::GRADE_1 => '一年級',
            self::GRADE_2 => '二年級',
            self::GRADE_3 => '三年級',
            self::GRADE_4 => '四年級',
            self::GRADE_5 => '五年級',
            self::GRADE_6 => '六年級',
            self::KINDERGARTEN_SMALL => '幼兒園-小班',
            self::KINDERGARTEN_MIDDLE => '幼兒園-中班',
            self::KINDERGARTEN_BIG => '幼兒園-大班',
            self::GRADUATED => '已畢業/離校',
        };
    }

    public function getColor(): string|array|null
    {
        return match ($this) {
            self::GRADE_6, self::KINDERGARTEN_BIG => 'danger',  // 畢業班用紅色警示
            self::GRADE_1, self::KINDERGARTEN_SMALL => 'success', // 新生班用綠色
            self::GRADUATED => 'gray', // 灰色，代表歷史
            default => 'info',
        };
    }

    /**
     * 向下相容 Model Accessor
     */
    public function label(): string
    {
        return $this->getLabel();
    }

    /**
     * 定義升級路徑
     * 回傳 null 代表該階段結束 (畢業)
     */
    public function next(): ?self
    {
        return match ($this) {
            // 幼兒園升級路徑
            self::KINDERGARTEN_SMALL => self::KINDERGARTEN_MIDDLE,
            self::KINDERGARTEN_MIDDLE => self::KINDERGARTEN_BIG,

            // [關鍵決策點] 幼兒園大班 -> 畢業 (轉入 GRADUATED 狀態，而非 null)
            self::KINDERGARTEN_BIG => self::GRADUATED,

            // 國小升級路徑
            self::GRADE_1 => self::GRADE_2,
            self::GRADE_2 => self::GRADE_3,
            self::GRADE_3 => self::GRADE_4,
            self::GRADE_4 => self::GRADE_5,
            self::GRADE_5 => self::GRADE_6,
            self::GRADE_6 => self::GRADUATED, // 國小畢業 -> 99

            self::GRADUATED => null, // 99 -> 虛無

            default => null,
        };
    }

    public function getStage(): SemesterStage
    {
        return match ($this) {
            self::KINDERGARTEN_SMALL, self::KINDERGARTEN_MIDDLE, self::KINDERGARTEN_BIG => SemesterStage::Kindergarten,
            default => SemesterStage::Elementary,
        };
    }
}
