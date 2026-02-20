<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StaffCheckIn extends Model
{
    use HasFactory;

    protected $fillable = [
        'school_id',
        'user_id',
        'staff_id',
        'checkin_code',
        'checked_at',
        'ip_address',
        'user_agent',
    ];

    protected $casts = [
        'checked_at' => 'datetime',
    ];

    public function school()
    {
        return $this->belongsTo(School::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function staff()
    {
        return $this->belongsTo(Staff::class);
    }
}
