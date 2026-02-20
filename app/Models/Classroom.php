<?php

namespace App\Models;

use App\Enums\GradeLevel;
use App\Enums\SemesterStage;
use App\Models\Traits\BelongsToSchool;
use App\Services\ClassroomService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Classroom extends Model
{
    use BelongsToSchool, HasFactory, LogsActivity, SoftDeletes;

    protected $table = 'classrooms';

    protected $fillable = [
        'school_id',
        'stage',
        'grade',
        'graduated_year',
        'class_name',
        'sort_order',
        'school_staff_id',
        'teacher_meal_qty',
        'class_type',
        'location',
        'caterer_id',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'teacher_meal_qty' => 'integer',
        'stage' => SemesterStage::class,
        'grade' => GradeLevel::class,
    ];

    protected static function boot()
    {
        parent::boot();

        static::saving(function ($classroom) {
            // 只有當影響排序的欄位變動時才重算
            if ($classroom->isDirty(['class_name', 'grade', 'stage'])) {

                // 1. 基礎分 (區分部別)
                $baseScore = match ($classroom->stage) {
                    SemesterStage::Kindergarten => 10000,
                    SemesterStage::Elementary => 20000,
                    default => 90000,
                };

                // 2. 年級分 (GradeLevel 是 Int Enum)
                $gradeScore = ($classroom->grade->value ?? 0) * 100;

                // 3. 班名分
                $nameScore = ClassroomService::getClassNameWeight($classroom->class_name);

                $classroom->sort_order = $baseScore + $gradeScore + $nameScore;
            }
        });
    }

    // --- Accessors (現代化寫法) ---

    /**
     * 取得班級全名 (ex: 一年甲班)
     */
    protected function fullName(): Attribute
    {
        return Attribute::make(
            get: function () {
                $gradeLabel = $this->grade->getLabel();
                $className = $this->class_name;

                // 移除 "年級" 字眼來進行比對 (例如 "四年級" -> "四年")
                $gradeKeyword = str_replace('級', '', $gradeLabel);

                if (str_contains($className, $gradeKeyword)) {
                    return $className;
                }

                return "{$gradeLabel}{$className}";
            },
        );
    }

    /**
     * 取得班級全名 (Student Model 的相容別名)
     */
    protected function classFullName(): Attribute
    {
        return $this->fullName();
    }

    // --- Relationships ---

    /**
     * 班導師 (主要窗口)
     */
    public function teacher(): BelongsTo
    {
        return $this->belongsTo(Staff::class, 'school_staff_id')->withDefault([
            'name' => '未定',
        ]);
    }

    public function students(): HasMany
    {
        return $this->hasMany(Student::class);
    }

    public function caterer(): BelongsTo
    {
        return $this->belongsTo(Caterer::class);
    }

    // --- Scopes ---

    /**
     * 預設排序：依照 sort_order 小到大
     */
    public function scopeSorted(Builder $query): void
    {
        $query->orderBy('sort_order', 'asc');
    }

    /**
     * 只抓有效班級
     */
    public function scopeActive(Builder $query): void
    {
        $query->where('is_active', true);
    }

    /**
     * 依學制篩選
     */
    public function scopeInStage(Builder $query, SemesterStage $stage): void
    {
        $query->where('stage', $stage);
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logFillable()
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }
}
