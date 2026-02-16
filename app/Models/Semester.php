<?php

namespace App\Models;

use App\Enums\SemesterStage;
use App\Enums\StaffMealMode;
use App\Models\Pivots\CatererSemester;
use App\Models\Traits\BelongsToSchool;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Semester extends Model
{
    use BelongsToSchool;
    use HasFactory, LogsActivity, SoftDeletes; // 引入之前傳授的護法心經，確保不會撈到別校的學期

    /**
     * [第二式：守門員]
     * 定義可被批量寫入的欄位
     */
    protected $fillable = [
        'school_id',
        'stage',
        'code',
        'name',
        'start_date',
        'end_date',
        'min_stop_days',
        'staff_stop_days',
        'public_leave_days',
        'personal_stop_days', // [新增]
        'sick_stop_days',     // [新增]
        'stop_cut_off_time',  // [新增]
        'group_min_stop_days', // [新增]
        'auto_approve_mode',   // [新增]
        'staff_meal_mode',
        'meal_price',
        'cost_breakdown', // JSON
        'fruit_days',     // JSON
        'is_active',
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logFillable()
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    /**
     * [第三式：易容術 (Casting)]
     * 將資料庫的冰冷數據，轉化為 PHP 的溫暖物件
     */
    protected $casts = [
        'stage' => SemesterStage::class,
        'staff_meal_mode' => StaffMealMode::class,
        'start_date' => 'date',       // 自動轉為 Carbon 物件，方便做日期比較
        'end_date' => 'date',         // 自動轉為 Carbon 物件
        'cost_breakdown' => 'array',  // 自動將 JSON 字串轉為 PHP 陣列
        'fruit_days' => 'array',      // 自動將 JSON 字串轉為 PHP 陣列
        'is_active' => 'boolean',
        'auto_approve_mode' => 'boolean', // [新增]
        'meal_price' => 'integer',
        'min_stop_days' => 'integer',
        'group_min_stop_days' => 'integer', // [新增]
    ];

    // =========================================================================
    // [第四式：人際關係 (Relationships)]
    // =========================================================================

    /**
     * 關聯：行事曆例外 (補課、放假)
     */
    public function exceptions(): HasMany
    {
        return $this->hasMany(SemesterException::class);
    }

    /**
     * 關聯：合作廠商 (本學期簽約的廠商)
     */
    public function caterers(): \Illuminate\Database\Eloquent\Relations\BelongsToMany
    {
        return $this->belongsToMany(Caterer::class)
            ->using(CatererSemester::class)
            ->withPivot('service_meals')
            ->withTimestamps();
    }

    // =========================================================================
    // [第五式：內功心法 (Scopes) - 讓查詢更優雅]
    // =========================================================================

    /**
     * 查詢：只找「啟用中」的學期
     * 用法：Semester::active()->get();
     */
    public function scopeActive(Builder $query): void
    {
        $query->where('is_active', true);
    }

    /**
     * 查詢：只找「國小部」
     * 用法：Semester::primary()->get();
     */
    public function scopePrimary(Builder $query): void
    {
        $query->where('stage', SemesterStage::Elementary);
    }

    /**
     * 查詢：只找「附幼部」
     */
    public function scopeKindergarten(Builder $query): void
    {
        $query->where('stage', SemesterStage::Kindergarten);
    }

    /**
     * 查詢：找出包含「今天」的那個學期
     * 用法：Semester::current()->first();
     */
    public function scopeCurrent(Builder $query): void
    {
        $today = now()->toDateString();
        $query->whereDate('start_date', '<=', $today)
            ->whereDate('end_date', '>=', $today);
    }

    // =========================================================================
    // [第六式：絕招 (Helpers & Accessors) - 解決複雜邏輯]
    // =========================================================================

    /**
     * 判斷某個日期是否在這個學期範圍內
     */
    public function containsDate($date): bool
    {
        if (! $date instanceof Carbon) {
            $date = Carbon::parse($date);
        }

        return $date->between($this->start_date, $this->end_date);
    }

    /**
     * 判斷某一天是否為「水果日」
     * $date: Carbon object or string
     */
    public function isFruitDay($date): bool
    {
        if (! $date instanceof Carbon) {
            $date = Carbon::parse($date);
        }

        // 取出這天的星期幾 (0=週日, 1=週一 ... 6=週六)
        $dayOfWeek = $date->dayOfWeek;

        // 檢查 fruit_days 陣列中是否有這個數字
        // fruit_days 可能為 null，需防呆
        $fruitDays = $this->fruit_days ?? [];

        return in_array($dayOfWeek, $fruitDays);
    }

    /**
     * 計算「退費金額」 (從 JSONB 中提取)
     * 這就是老夫之前說的：應對部分退費的絕招
     */
    public function getRefundAmountAttribute(): int
    {
        $breakdown = $this->cost_breakdown ?? [];

        // 假設規則：退費 = 食材費 (ingredients)
        // 若附幼要退「點心費」，可在這裡依據 stage 寫不同邏輯
        if ($this->stage === SemesterStage::Kindergarten) {
            // 附幼範例：退午餐+點心食材
            return ($breakdown['lunch_ingredients'] ?? 0) +
                ($breakdown['snack_ingredients'] ?? 0);
        }

        // 國小範例：只退食材
        return $breakdown['ingredients'] ?? 0;
    }

    /**
     * 判斷某一天是否「不供餐」 (結合了週末與例外表)
     * 這是生成菜單或訂單的核心邏輯
     */
    public function isDayOff($date): bool
    {
        $date = Carbon::parse($date);

        // 1. 先查「例外表」 (需 Eager Load: ->load('exceptions'))
        $exception = $this->exceptions->firstWhere('date', $date->toDateString());

        if ($exception) {
            // 0:放假, 2:活動不供餐, 3:天災 => 都是 DayOff
            // 1:補課 => 不是 DayOff (要吃飯)
            return $exception->type !== 1;
        }

        // 2. 若無例外，則看「常規」 (預設週末放假)
        return $date->isWeekend();
    }

    /**
     * 計算兩個日期之間的供餐日 (Workdays) 總數
     */
    public function countWorkdays(string|Carbon $startDate, string|Carbon $endDate): int
    {
        $start = Carbon::parse($startDate)->startOfDay();
        $end = Carbon::parse($endDate)->startOfDay();

        if ($start->gt($end)) {
            return 0;
        }

        $count = 0;
        $current = $start->copy();

        // 預先載入 exceptions 避免 N+1 (如果尚未載入)
        if (! $this->relationLoaded('exceptions')) {
            $this->load('exceptions');
        }

        while ($current->lte($end)) {
            if (! $this->isDayOff($current)) {
                $count++;
            }
            $current->addDay();
        }

        return $count;
    }
}
