<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class MealCancellationGroup extends Model
{
    protected $fillable = [
        'school_id',
        'name',
        'description',
        'created_by',
    ];

    /**
     * 所屬學校
     */
    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    /**
     * 建立者
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * 群組成員（學生）
     */
    public function students(): BelongsToMany
    {
        return $this->belongsToMany(Student::class, 'meal_cancellation_group_student', 'group_id', 'student_id')
            ->withTimestamps();
    }

    /**
     * Scope: 限定學校
     */
    public function scopeForSchool($query, $schoolId)
    {
        return $query->where('school_id', $schoolId);
    }

    /**
     * Scope: 限定建立者
     */
    public function scopeCreatedBy($query, $userId)
    {
        return $query->where('created_by', $userId);
    }

    /**
     * 取得群組的學生數量
     */
    public function getStudentCountAttribute(): int
    {
        return $this->students()->count();
    }
}
