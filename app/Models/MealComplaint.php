<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MealComplaint extends Model
{
    protected $fillable = [
        'school_id',
        'classroom_id',
        'staff_id',
        'occurred_at',
        'content',
        'photo_path',
        'status',
        'caterer_reply',
        'resolved_at',
        'rating', // [新增] 評分 (1-5)
    ];

    protected $casts = [
        'occurred_at' => 'date',
        'resolved_at' => 'datetime',
        'rating' => 'integer',
    ];

    /**
     * 所屬學校
     */
    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    /**
     * 發生問題的班級
     */
    public function classroom(): BelongsTo
    {
        return $this->belongsTo(Classroom::class);
    }

    /**
     * 回報者 (老師/行政)
     */
    public function staff(): BelongsTo
    {
        return $this->belongsTo(Staff::class);
    }

    /**
     * 學校範圍篩選
     */
    public function scopeForSchool(Builder $query, int $schoolId): Builder
    {
        return $query->where('school_id', $schoolId);
    }
}
