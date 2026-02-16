<?php

namespace App\Services;

use App\Models\LineAccount;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Laravel\Socialite\Contracts\User as SocialiteUser;

class LineIdentityService
{
    /**
     * 處理 Socialite LINE 登入回傳的 Users
     */
    public function handleSocialiteCallback(SocialiteUser $socialiteUser): User
    {
        return DB::transaction(function () use ($socialiteUser) {
            // 1. 嘗試透過 line_user_id 尋找現有的 LineAccount
            // CRITICAL: Must bypass global scopes to find accounts across all schools
            $lineAccount = LineAccount::withoutGlobalScopes()
                ->where('line_user_id', $socialiteUser->getId())
                ->first();

            if ($lineAccount) {
                // 情境 A: 已經綁定過，更新 Token 與 Profile
                $this->updateLineProfile($lineAccount, $socialiteUser);

                return $lineAccount->user;
            }

            // 情境 B: 尚未綁定，需要建立新帳號或綁定現有 User
            // 這裡我們先實作「自動註冊新帳號」邏輯
            // 若未來需要「綁定已登入帳號」，則需判斷 Auth::check()
            return $this->registerNewUserWithLine($socialiteUser);
        });
    }

    /**
     * 建立新使用者並綁定 LINE
     */
    protected function registerNewUserWithLine(SocialiteUser $socialiteUser): User
    {
        // 1. 建立 User (Email 可能與 LINE 不一致，這裡先用假 Email 或該 User 的 Email)
        // 注意：LINE Login 不一定會回傳 Email，需視 Provider 設定
        $email = $socialiteUser->getEmail() ?? "line_{$socialiteUser->getId()}@example.com";

        $user = User::firstOrCreate(
            ['email' => $email],
            [
                'name' => $socialiteUser->getName() ?? 'LINE User',
                'password' => Hash::make(Str::random(32)), // 隨機密碼，因為是用 LINE 登入
                'avatar_url' => $socialiteUser->getAvatar(),
                'is_profile_completed' => false, // 標記為未完成，強制進入補全流程
            ]
        );

        // 2. 建立 LineAccount 連結
        // CRITICAL: Explicitly set school_id from user to satisfy NOT NULL constraint
        $lineAccount = new LineAccount([
            'user_id' => $user->id,
            'school_id' => $user->school_id, // Inherit from user (might be null initially)
        ]);

        $this->updateLineProfile($lineAccount, $socialiteUser);

        return $user;
    }

    /**
     * 更新 LineAccount 的資料 (Profile & Token)
     */
    protected function updateLineProfile(LineAccount $lineAccount, SocialiteUser $socialiteUser): void
    {
        /** @var \Laravel\Socialite\Two\User $socialiteUser */
        $lineAccount->fill([
            'line_user_id' => $socialiteUser->getId(), // 確保 ID 寫入 (對新建立物件)
            'display_name' => $socialiteUser->getName(),
            'avatar_url' => $socialiteUser->getAvatar(),
            'access_token' => $socialiteUser->token,
            'refresh_token' => $socialiteUser->refreshToken,
            'token_expires_at' => now()->addSeconds($socialiteUser->expiresIn),
            'raw_response' => $socialiteUser->getRaw(),
        ])->save();
    }
}
