<?php

namespace App\Models;

use App\Enums\CatererSupplyType;
use App\Models\Pivots\CatererSemester;
use App\Models\Traits\BelongsToSchool;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Caterer extends Model
{
    use BelongsToSchool, HasFactory, LogsActivity, SoftDeletes;

    protected $fillable = [
        'school_id',
        'name',
        'supply_type',
        'tax_id',
        'phone',
        'address',
        'contact_person',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'supply_type' => CatererSupplyType::class,
    ];

    /**
     * 獲取該廠商合作過的所有學期
     */
    public function semesters(): BelongsToMany
    {
        return $this->belongsToMany(Semester::class)
            ->using(CatererSemester::class)
            ->withPivot('service_meals')
            ->withTimestamps();
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logFillable()
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }
}
