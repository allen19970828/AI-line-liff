<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class School extends Model
{
    use HasFactory, LogsActivity, SoftDeletes; // [關鍵] 啟用心法

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($school) {
            if (! $school->checkin_code) {
                $school->checkin_code = 'chk_'.strtolower(\Illuminate\Support\Str::random(12));
            }
        });
    }

    /**
     * [第一要素：防護罩]
     * $fillable (可批量賦值屬性)
     * 只有寫在這裡的欄位，才允許透過 create() 或 update() 直接注入。
     * 這是防止「隔空打牛」（惡意修改不該改的欄位）的關鍵防禦。
     */
    protected $fillable = [
        'code',
        'name',
        'tax_id',
        'city',
        'district',
        'address',
        'phone',
        'website',
        'is_active',
        'checkin_code',
        // 注意：id, created_at, updated_at, deleted_at 不需要寫在這裡，系統會自動處理
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logFillable()
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    /**
     * [第二要素：易容術]
     * $casts (型別轉換)
     * 資料庫裡的 tinyint(1) 或 string，拿出來時要自動變成 PHP 的格式。
     */
    protected $casts = [
        'is_active' => 'boolean', // 讓它在 PHP 裡變成 true/false，而不是 1/0
        // 若你有用 jsonb，記得加 'options' => 'array',
    ];

    /**
     * [第三要素：人際關係]
     * Relationships
     * 雖然你斬斷了 owner_id，但學校與人依然有緣。
     * 這裡定義學校與用戶的關係：一所學校可以有多個用戶（老師、家長）。
     */
    public function users()
    {
        return $this->hasMany(User::class);
    }

    /**
     * [父子連心] 學校 -> 學期
     */
    public function semesters(): HasMany
    {
        return $this->hasMany(Semester::class);
    }

    /**
     * [隔代傳功] 學校 -> (透過學期) -> 行事曆例外
     * * 用法：$school->calendarExceptions()->get();
     * 效果：自動幫你撈出該校所有學期底下的所有例外日子
     */
    public function calendarExceptions(): HasManyThrough
    {
        return $this->hasManyThrough(
            SemesterException::class, // 目標 (孫子)
            Semester::class,          // 中介 (爸爸)
            'school_id',              // 爸爸身上的外鍵 (指向爺爺)
            'semester_id',            // 孫子身上的外鍵 (指向爸爸)
            'id',                     // 爺爺的主鍵
            'id'                      // 爸爸的主鍵
        );
    }
}
