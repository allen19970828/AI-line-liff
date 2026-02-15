<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        \Illuminate\Support\Facades\Gate::policy(
            \Rappasoft\LaravelAuthenticationLog\Models\AuthenticationLog::class,
            \App\Policies\AuthenticationLogPolicy::class
        );

        // 宗師指點：確保 Excel 匯入時中文標題不會被轉成 slug
        \Maatwebsite\Excel\Imports\HeadingRowFormatter::default('none');
    }
}
