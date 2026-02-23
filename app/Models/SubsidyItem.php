<?php

namespace App\Models;

use App\Enums\SubsidySource;
use App\Models\Traits\BelongsToSchool;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class SubsidyItem extends Model
{
    use BelongsToSchool, HasFactory, LogsActivity, SoftDeletes;

    protected $fillable = [
        'school_id',
        'semester_id',
        'name',
        'code',
        'description',
        'target_roles',
        'identity_tags',
        'source_type',
        'is_full_coverage',
        'amount_per_meal',
        'budget_limit',
        'priority',
        'is_active',
    ];

    protected $casts = [
        'target_roles' => 'array',
        'identity_tags' => 'array',
        'source_type' => SubsidySource::class,
        'is_full_coverage' => 'boolean',
        'is_active' => 'boolean',
        'amount_per_meal' => 'integer',
        'budget_limit' => 'integer',
        'priority' => 'integer',
    ];

    public function semester(): BelongsTo
    {
        return $this->belongsTo(Semester::class);
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logFillable()
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }
}
