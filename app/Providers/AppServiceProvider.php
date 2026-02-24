<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\View;

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
        // Condividi op_token a tutte le view
        View::composer('*', function ($view) {
            $request = request();
            $opToken = $request->attributes->get('op_token')
                ?? $request->query('op_token')
                ?? null;
            $view->with('opToken', $opToken);
        });
    }
}
