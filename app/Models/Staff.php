<?php

namespace App\Models;

use App\Enums\DietaryType;
use App\Enums\SemesterStage;
use App\Enums\StaffRole; // [修正] 專案標準 Enum
use App\Models\Traits\BelongsToSchool;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Staff extends Model
{
    use BelongsToSchool, HasFactory, LogsActivity, SoftDeletes;

    protected $table = 'school_staff';

    protected $fillable = [
        'school_id',
        'user_id',
        'lunch_code',    // 這是你的感應卡號
        'name',
        'stage',         // 部別 (國小/幼兒園)
        'staff_role',    // [新增] 身分類別 (取代 is_tutor)
        'job_title',     // 顯示用職稱
        'dietary_type',  // 飲食習慣
        'is_meal_user',  // 是否搭伙
        'meal_days',     // 兼職設定
        'is_active',
    ];

    protected $casts = [
        'is_meal_user' => 'boolean',
        'is_active' => 'boolean',
        'meal_days' => 'array', // 自動處理 JSONB

        // --- 核心 Enum 轉型 ---
        'stage' => SemesterStage::class, // [修正]
        'staff_role' => StaffRole::class, // [新增]
        'dietary_type' => DietaryType::class,
    ];

    // --- 核心關聯 ---

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * [宗師新增] 透過 User 關聯到 LINE 帳號
     */
    public function lineAccount(): \Illuminate\Database\Eloquent\Relations\HasOneThrough
    {
        return $this->hasOneThrough(
            LineAccount::class,
            User::class,
            'id',           // users.id
            'user_id',      // line_accounts.user_id
            'user_id',      // staff.user_id
            'id'            // users.id
        );
    }

    // 宗師指點：移除重複的 school() 關聯，BelongsToSchool Trait 已包含

    /**
     * [宗師新增] 連結到他帶的班級
     * 這是「反向一對一」或「一對多」：因為 foreign key (school_staff_id) 是在 classrooms 表上
     */
    public function classrooms(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Classroom::class, 'school_staff_id');
    }

    /**
     * 停餐申請紀錄 (多型關聯)
     */
    public function mealCancelRequests(): \Illuminate\Database\Eloquent\Relations\MorphMany
    {
        return $this->morphMany(MealCancelRequest::class, 'cancellable');
    }

    // --- 業務邏輯 (Business Logic) ---

    /**
     * [計費核心] 判斷該員工是否需要繳費
     * 目前僅判斷基本用餐意願。
     * 未來將整合「補助管理服務」來處理導師免費、特教補助等複雜規則。
     */
    public function getNeedsToPayAttribute(): bool
    {
        // 1. 本人設定不吃 -> 免費
        if (! $this->is_meal_user) {
            return false;
        }

        // 2. 預留：未來這裡會呼叫 SubsidyService 判斷是否符合補助資格
        // if (SubsidyService::check($this)) { return false; }

        // 3. 預設 -> 自費
        return true;
    }

    // --- 作用域 (Scopes) ---

    /**
     * 找出具備「教師資格」的人 (潛在導師群)
     */
    public function scopeTutors(Builder $query): void
    {
        // 只有 Teacher 身分才有資格當導師
        $query->where('staff_role', StaffRole::Teacher->value)
            ->where('is_active', true);
    }

    public function scopeActive(Builder $query): void
    {
        $query->where('is_active', true);
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logFillable()
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }
}
