<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class NationalHoliday extends Model
{
    use HasFactory, LogsActivity;

    // [第一式：常數定義]
    // 雖然資料庫存字串，但在程式裡我們用常數，避免打錯字
    const TYPE_HOLIDAY = 'holiday'; // 放假 (紅字)

    const TYPE_MAKEUP = 'makeup';   // 補班/補課 (要上班上課)

    protected $fillable = [
        'date',
        'type',
        'name',
        'year',
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logFillable()
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    /**
     * [第二式：易容術 (Casting)]
     * date 轉為 Carbon 物件，方便做日期比對
     * year 轉為整數
     */
    protected $casts = [
        'date' => 'date',
        'year' => 'integer',
    ];

    /**
     * [第三式：自動心法 (Booted)]
     * 宗師指點：當你寫入 date 時，系統自動算出 year 填進去。
     * 這樣你在匯入資料時，少寫一個欄位，也不會出錯。
     */
    protected static function booted(): void
    {
        static::saving(function (NationalHoliday $holiday) {
            if ($holiday->date) {
                // 這裡看你想存西元(2026)還是民國(115)
                // 建議存西元，與資料庫標準一致，顯示時再轉民國
                $date = Carbon::parse($holiday->date);
                $holiday->year = (int) $date->format('Y');

                // 若要存民國年，改為：
                // $holiday->year = $holiday->date->year - 1911;
            }
        });
    }

    // =========================================================================
    // [第四式：絕招 (Scopes) - 這是給學期匯入用的]
    // =========================================================================

    /**
     * 查詢：找出某個學期範圍內的所有國定假日
     * 用法：NationalHoliday::inRange('2025-09-01', '2026-01-20')->get();
     */
    public function scopeInRange(Builder $query, $startDate, $endDate): void
    {
        $query->whereBetween('date', [$startDate, $endDate]);
    }

    /**
     * 查詢：只找「放假」的日子
     */
    public function scopeHolidays(Builder $query): void
    {
        $query->where('type', self::TYPE_HOLIDAY);
    }

    /**
     * 查詢：只找「補班」的日子
     */
    public function scopeMakeups(Builder $query): void
    {
        $query->where('type', self::TYPE_MAKEUP);
    }

    // =========================================================================
    // [第五式：判斷輔助 (Helpers)]
    // =========================================================================

    /**
     * 這天是否為補班日 (雖然是週六，但要供餐)
     */
    public function isMakeupDay(): bool
    {
        return $this->type === self::TYPE_MAKEUP;
    }

    /**
     * 取得類型名稱 (給 Filament 用)
     */
    public function getTypeName(): string
    {
        return match ($this->type) {
            self::TYPE_HOLIDAY => '放假',
            self::TYPE_MAKEUP => '補行上班/上課',
            default => '未知',
        };
    }

    /**
     * 取得顏色 (給 Filament Badge 用)
     */
    public function getColor(): string
    {
        return match ($this->type) {
            self::TYPE_HOLIDAY => 'danger',  // 放假用紅色
            self::TYPE_MAKEUP => 'warning',  // 補班用橘色/黃色
            default => 'gray',
        };
    }
}
