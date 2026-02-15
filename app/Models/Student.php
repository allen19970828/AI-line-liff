<?php

namespace App\Models;

use App\Enums\DietaryType;
use App\Enums\GradeLevel;
use App\Enums\SemesterStage;
use App\Enums\StudentIdentity;
use App\Enums\StudentStatus;
use App\Enums\SubsidyReason; // [新增]
use App\Models\Traits\BelongsToSchool; // [新增]
use Illuminate\Database\Eloquent\Builder; // [新增]
use Illuminate\Database\Eloquent\Factories\HasFactory; // [新增]
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany; // [新增]
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions; // [新增]
use Spatie\Activitylog\Traits\LogsActivity; // [新增]

class Student extends Model
{
    use BelongsToSchool, HasFactory, LogsActivity, SoftDeletes;

    /**
     * 臨時屬性 (Transient Properties)
     * 用於 Observer 或核心邏輯傳遞生效日期
     */
    public ?string $effective_date_override = null;

    protected $fillable = [
        'school_id',
        'classroom_id',
        'stage',
        'name',
        'student_code',
        'seat_number',
        'status',
        'is_meal_user',
        'dietary_type',
        'eat_morning_snack',
        'eat_lunch',
        'eat_afternoon_snack',
        'meal_days',
        'identity',
        'subsidy_reason',
        'subsidy_document',
        'payment_method',
        'bank_code',
        'bank_account',
        'account_holder',
        'joined_at',
        'left_at',
        'note',
        'grade_level', // [預留] 方便未來快照
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'stage' => SemesterStage::class,
            'status' => StudentStatus::class,
            'dietary_type' => DietaryType::class,
            'identity' => StudentIdentity::class,
            'subsidy_reason' => SubsidyReason::class,
            'grade_level' => GradeLevel::class, // [新增]
            'is_meal_user' => 'boolean',
            'eat_morning_snack' => 'boolean',
            'eat_lunch' => 'boolean',
            'eat_afternoon_snack' => 'boolean',
            'meal_days' => 'array',
            'joined_at' => 'date',
            'left_at' => 'date',
        ];
    }

    // --- 核心關聯 ---

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    public function classroom(): BelongsTo
    {
        return $this->belongsTo(Classroom::class);
    }

    /**
     * 學生狀態歷史 (時光溯源)
     */
    public function statusHistories(): HasMany
    {
        return $this->hasMany(StudentStatusHistory::class)->orderBy('start_date', 'desc');
    }

    /**
     * 停餐申請紀錄 (多型關聯)
     */
    public function mealCancelRequests(): \Illuminate\Database\Eloquent\Relations\MorphMany
    {
        return $this->morphMany(MealCancelRequest::class, 'cancellable');
    }

    // --- 業務邏輯 (時光溯源) ---

    /**
     * 取得特定日期的身分/狀態快照
     * 會優先查找歷史紀錄，若無則回傳當前值
     */
    public function getSnapshotAtDate($date, string $field = 'identity')
    {
        $history = $this->statusHistories->first(function ($record) use ($date) {
            $start = $record->start_date;
            $end = $record->end_date;

            return $date >= $start && (is_null($end) || $date <= $end);
        });

        return $history ? $history->$field : $this->$field;
    }

    /**
     * 舊版相容門面 (Alias for getSnapshotAtDate)
     */
    public function getIdentityAtDate($date)
    {
        return $this->getSnapshotAtDate($date, 'identity');
    }

    /**
     * 作用域：只抓取在學學生
     */
    public function scopeActive(Builder $query): void
    {
        $query->where('status', StudentStatus::Active);
    }

    // --- 便利 Accessors ---

    /**
     * 取得年級 (優先使用快照欄位，若無則從班級取得)
     */
    public function getGradeAttribute(): ?GradeLevel
    {
        return $this->grade_level ?? $this->classroom?->grade;
    }

    /**
     * 取得年級數值 (1-6) - 相容性 Accessor
     */
    public function getGradeValueAttribute(): int
    {
        return $this->grade?->value ?? 0;
    }

    /**
     * 取得班級全名 (如：一年一班)
     */
    public function getClassFullNameAttribute(): string
    {
        return $this->classroom?->full_name ?? '未分班';
    }

    // --- 日誌設定 (Spatie Activity Log) ---

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logFillable()
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->logExcept(['updated_at']);
    }
}
