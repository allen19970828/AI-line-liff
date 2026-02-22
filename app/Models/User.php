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
     *
     * 三層判定機制：
     * 1. 環境變數 SUPER_ADMIN_EMAILS (維修後門)
     * 2. 資料庫 super_admin 角色 (Filament Shield)
     */
    public function canBypassSchoolScope(): bool
    {
        // 第一層：環境變數維修後門
        $superAdminEmails = config('app.super_admin_emails', []);
        if ($this->email && in_array($this->email, $superAdminEmails, true)) {
            return true;
        }

        // 第二層：資料庫角色 (spatie/laravel-permission)
        if (method_exists($this, 'hasRole')) {
            return $this->hasRole('super_admin');
        }

        return false;
    }

    /**
     * 判定是否為「校級管理員」，僅限校內管理權限，不具備跨校能力
     */
    public function isSchoolAdmin(): bool
    {
        if ($this->canBypassSchoolScope()) {
            return true;
        }

        if (method_exists($this, 'hasRole')) {
            return $this->hasRole('school_admin');
        }

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
