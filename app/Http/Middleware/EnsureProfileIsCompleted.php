<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureProfileIsCompleted
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // 1. 未登入者不需檢查 (由 auth middleware 負責)
        if (! Auth::check()) {
            return $next($request);
        }

        $user = Auth::user();

        // 2. 已完成者直接放行
        if ($user->is_profile_completed) {
            return $next($request);
        }

        // 3.若正在訪問補全頁面或登出，則放行 (避免無窮迴圈)
        if ($request->routeIs('profile.complete', 'profile.update', 'logout', 'filament.admin.auth.logout')) {
            return $next($request);
        }

        // 4. 未完成且試圖訪問其他頁面 -> 強制轉向
        return redirect()->route('profile.complete');
    }
}
