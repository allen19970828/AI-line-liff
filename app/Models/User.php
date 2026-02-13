<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'school_id',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'school_id' => 'integer',
        ];
    }

    /**
     * 判定是否為「上帝視角」(Super Admin)，可以無視學校隔離
     */
    public function canBypassSchoolScope(): bool
    {
        // 方案一：如果你有 email 是特定值的上帝
        if ($this->email === 'admin@example.com') {
            return true;
        }

        // 方案二：如果你使用了 Filament Shield (spatie/laravel-permission)
        // 可以檢查是否有 super_admin 角色
        if (method_exists($this, 'hasRole')) {
            return $this->hasRole('super_admin');
        }

        // 預設：沒有國境之南，就沒有上帝
        return false;
    }

    /**
     * 關聯：歸屬學校
     */
    public function school(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(School::class);
    }
}
