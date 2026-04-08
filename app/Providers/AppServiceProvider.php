<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use App\Events\PhaseCompleted;
use App\Listeners\PropagateAvailability;
use App\Models\OrdineFase;
use App\Observers\OrdineFaseObserver;

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
        // Mossa 37: propaga disponibilità quando una fase viene completata
        Event::listen(PhaseCompleted::class, PropagateAvailability::class);

        // Audit Log: traccia automaticamente cambio stato e eliminazione fasi
        OrdineFase::observe(OrdineFaseObserver::class);

        // Rate Limiting
        RateLimiter::for('login', function (Request $request) {
            return Limit::perMinutes(15, 5)->by($request->ip())
                ->response(function () {
                    return response('Troppi tentativi di accesso. Riprova tra qualche minuto.', 429);
                });
        });

        RateLimiter::for('api', function (Request $request) {
            return Limit::perMinute(60)->by($request->ip());
        });

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

            // Lista operatori per menzioni chat @
            if (!$view->offsetExists('operatori_chat')) {
                try {
                    $view->with('operatori_chat', \App\Models\Operatore::where('attivo', true)->orderBy('cognome')->get());
                } catch (\Exception $e) {
                    $view->with('operatori_chat', collect());
                }
            }
        });
    }
}
