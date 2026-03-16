<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Facades\Event;
use App\Events\PhaseCompleted;
use App\Listeners\PropagateAvailability;

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
        // Mossa 37: propagazione istantanea quando una fase viene completata
        Event::listen(PhaseCompleted::class, PropagateAvailability::class);

        // Forza HTTPS quando si accede da tunnel (tnnl.in non manda X-Forwarded-Proto)
        if (str_contains(request()->getHost(), 'tnnl.in')) {
            URL::forceScheme('https');
        }

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
