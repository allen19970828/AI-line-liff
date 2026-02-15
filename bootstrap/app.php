<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
        apiPrefix: 'api',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'completed' => \App\Http\Middleware\EnsureProfileIsCompleted::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->render(function (\Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException $e, \Illuminate\Http\Request $request) {
            // 如果是 Filament 後台請求，且不是 API 請求 (API 通常回傳 JSON)
            if ($request->is('admin/*')) {
                // 使用 Filament 的通知功能發送警告
                \Filament\Notifications\Notification::make()
                    ->title('存取被拒 (Access Denied)')
                    ->body('您的權限不足以訪問該頁面，系統已自動將您踢回大廳。')
                    ->danger()
                    ->send();

                // 踢回首頁或儀表板
                return redirect()->to('/admin');
            }
        });
    })->create();
