<?php

namespace App\Http\Controllers;

use App\Services\LineIdentityService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Laravel\Socialite\Facades\Socialite;

class LineLoginController extends Controller
{
    public function __construct(protected LineIdentityService $lineIdentityService) {}

    /**
     * 重導向至 LINE 登入頁面
     */
    public function redirect(): RedirectResponse
    {
        return Socialite::driver('line')->redirect();
    }

    /**
     * 處理 LINE 登入回調
     */
    public function callback(): RedirectResponse
    {
        try {
            // 取得 Socialite User
            $socialiteUser = Socialite::driver('line')->user();

            // 透過 Service 處理綁定或註冊
            $user = $this->lineIdentityService->handleSocialiteCallback($socialiteUser);

            // 登入該 User
            Auth::login($user, true);

            // 轉導回首頁或後台
            return redirect()->intended('/admin');

        } catch (\Exception $e) {
            // 記錄錯誤並導回登入頁，或顯示錯誤訊息
            report($e);

            return redirect('/admin/login')->withErrors(['line_login' => 'LINE 登入失敗，請稍後再試。']);
        }
    }
}
