<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Filament\Models\Contracts\FilamentUser;
use Filament\Models\Contracts\HasAvatar;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Storage;
use Jeffgreco13\FilamentBreezy\Traits\TwoFactorAuthenticatable;
use Laravel\Sanctum\HasApiTokens;
use Rappasoft\LaravelAuthenticationLog\Traits\AuthenticationLoggable;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable implements FilamentUser, HasAvatar
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use AuthenticationLoggable, HasApiTokens, HasFactory, HasRoles, LogsActivity, Notifiable, TwoFactorAuthenticatable;

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logFillable()    // 紀錄所有可填寫欄位
            ->logOnlyDirty()   // 只紀錄有變動的部分
            ->dontSubmitEmptyLogs(); // 如果沒變動就不紀錄
    }

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
        'is_profile_completed',
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
            'is_profile_completed' => 'boolean',
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
     * 關聯：歸屬學校 (單數)
     * [宗師修正] 這是 BelongsTo，所以應該用單數
     */
    public function school(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(School::class, 'school_id');
    }

    /**
     * 取得 Filament 大頭照 URL
     */
    public function getFilamentAvatarUrl(): ?string
    {
        return $this->avatar_url ? Storage::url($this->avatar_url) : null;
    }

    /**
     * 關聯：LINE 身份帳號 (一對一)
     */
    public function lineAccount(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(LineAccount::class);
    }

    /**
     * 關聯：教職員檔案 (一對一)
     */
    public function staff(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(Staff::class);
    }

    /**
     * Determine if the user can access the Filament panel.
     */
    public function canAccessPanel(\Filament\Panel $panel): bool
    {
        return true;
    }
}
