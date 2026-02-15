<?php

namespace App\Models;

use App\Enums\StudentIdentity;
use App\Enums\StudentStatus;
use App\Enums\SubsidyReason;
use App\Models\Traits\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StudentStatusHistory extends Model
{
    use BelongsToSchool;

    protected $fillable = [
        'school_id',
        'student_id',
        'identity',
        'status',
        'subsidy_reason',
        'start_date',
        'end_date',
        'note',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'identity' => StudentIdentity::class,
            'status' => StudentStatus::class,
            'subsidy_reason' => SubsidyReason::class,
            'start_date' => 'date',
            'end_date' => 'date',
        ];
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }
}
