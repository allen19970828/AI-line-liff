<?php

namespace App\Models;

use App\Enums\Meals\CancelLeaveType;
use App\Enums\Meals\CancelRequestStatus;
use App\Models\Traits\BelongsToSchool;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MealCancelRequest extends Model
{
    use BelongsToSchool, HasFactory;

    protected $fillable = [
        'school_id',
        'cancellable_id',
        'cancellable_type',
        'start_date',
        'end_date',
        'leave_type',
        'scope_type',      // [新增]
        'classroom_id',    // [新增]
        'requested_by',    // [新增]
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

    /**
     * 關聯：關聯班級 (僅班級活動停餐)
     */
    public function classroom(): BelongsTo
    {
        return $this->belongsTo(Classroom::class);
    }

    /**
     * 關聯：申請人 (代為申請的老師)
     */
    public function requestedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by');
    }
}
