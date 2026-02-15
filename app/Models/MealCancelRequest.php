<?php

namespace App\Models;

use App\Enums\Meals\CancelLeaveType;
use App\Enums\Meals\CancelRequestStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class MealCancelRequest extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'cancellable_id',
        'cancellable_type',
        'start_date',
        'end_date',
        'leave_type',
        'reason',
        'status',
        'origin',
        'approved_by',
        'approved_at',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'approved_at' => 'datetime',
        'leave_type' => CancelLeaveType::class,
        'status' => CancelRequestStatus::class,
    ];

    /**
     * 多型關聯: 申請主體 (Student or Staff)
     */
    public function cancellable(): \Illuminate\Database\Eloquent\Relations\MorphTo
    {
        return $this->morphTo();
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }
}
