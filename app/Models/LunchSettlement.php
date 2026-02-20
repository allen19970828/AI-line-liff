<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LunchSettlement extends Model
{
    /** @use HasFactory<\Database\Factories\LunchSettlementFactory> */
    protected $fillable = [
        'school_id',
        'semester_id',
        'staff_id',
        'year_month',
        'days_count',
        'amount',
        'status',
        'check_in_dates',
        'snapshot_price',
    ];

    protected $casts = [
        'check_in_dates' => 'array',
        'days_count' => 'integer',
        'amount' => 'integer',
        'snapshot_price' => 'integer',
    ];

    public function school()
    {
        return $this->belongsTo(School::class);
    }

    public function semester()
    {
        return $this->belongsTo(Semester::class);
    }

    public function staff()
    {
        return $this->belongsTo(Staff::class);
    }
}
