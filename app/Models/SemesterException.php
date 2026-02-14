<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SemesterException extends Model
{
    use HasFactory;

    // [第一式：常數定義]
    // 讓代碼遠離魔術數字 (Magic Numbers)
    const TYPE_HOLIDAY = 0;   // 放假

    const TYPE_MAKEUP = 1;    // 補課

    const TYPE_ACTIVITY = 2;  // 活動(不供餐)

    const TYPE_TYPHOON = 3;   // 天災

    const TYPE_OTHER = 4;     // 其他

    protected $fillable = [
        'semester_id',
        'date',
        'type',
        'is_meal_day',
        'target_grades', // JSONB
        'description',
        'show_in_reports',
    ];

    /**
     * [第二式：易容術 (Casting)]
     * 關鍵：target_grades 必須轉為 array，date 轉為物件
     */
    protected $casts = [
        'date' => 'date',
        'is_meal_day' => 'boolean',
        'show_in_reports' => 'boolean',
        'target_grades' => 'array', // 自動將 JSON 轉為 PHP 陣列
        'type' => 'integer',
    ];

    /**
     * [第三式：尋根溯源]
     * 每一筆例外，都屬於一個學期
     */
    public function semester(): BelongsTo
    {
        return $this->belongsTo(Semester::class);
    }

    // =========================================================================
    // [第四式：絕招 (Business Logic)]
    // =========================================================================

    /**
     * 判斷此例外是否適用於某個年級
     *
     * * @param string|int $grade 學生的年級 (ex: "6")
     */
    public function appliesToGrade($grade): bool
    {
        // 1. 如果 target_grades 是 null，代表「全校適用」
        if (is_null($this->target_grades)) {
            return true;
        }

        // 2. 轉成字串比對，防止 6 (int) != "6" (string) 的慘案
        $grade = (string) $grade;

        // 3. 檢查該年級是否在名單中
        return in_array($grade, $this->target_grades);
    }

    /**
     * 取得類型名稱 (給前端或 Filament 顯示用)
     */
    public function getTypeName(): string
    {
        return match ($this->type) {
            self::TYPE_HOLIDAY => '放假',
            self::TYPE_MAKEUP => '補課(供餐)',
            self::TYPE_ACTIVITY => '學校活動',
            self::TYPE_TYPHOON => '天災停課',
            default => '其他',
        };
    }

    /**
     * 取得 Filament Badge 顏色
     */
    public function getColor(): string
    {
        return match ($this->type) {
            self::TYPE_HOLIDAY, self::TYPE_TYPHOON => 'danger', // 紅色警戒
            self::TYPE_MAKEUP => 'success',                     // 綠色通行
            self::TYPE_ACTIVITY => 'warning',                   // 黃色注意
            default => 'gray',
        };
    }
}
