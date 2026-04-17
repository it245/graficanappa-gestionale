<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use App\Http\Middleware\OwnerMiddleware;

use App\Http\Middleware\OperatoreAuth;
use App\Http\Middleware\AdminAuth;
use App\Http\Middleware\OwnerOrAdmin;
use App\Http\Middleware\SecurityHeaders;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        channels: __DIR__.'/../routes/channels.php',
        health: '/up',
    )
    ->withMiddleware(function ($middleware) {
        // Trust tunnel/proxy headers (tnnl.in e simili)
        $middleware->trustProxies(at: '*');

        // Security headers su tutte le risposte
        $middleware->append(SecurityHeaders::class);

        // Escludi CSRF per route owner che usano op_token
        $middleware->validateCsrfTokens(except: [
            'owner/aggiungi-riga',
            'owner/sync-onda',
            'owner/import',
            'owner/aggiorna-campo',
            'owner/cliche/set',
            'owner/cliche/clear',
            'operatore/prestampa/aggiorna-campo',
            'spedizione/sync-onda',
        ]);

        $middleware->alias([
            'operatore.auth' => OperatoreAuth::class,
            'owner' => \App\Http\Middleware\OwnerMiddleware::class,
            'admin' => AdminAuth::class,
            'owner.or.admin' => OwnerOrAdmin::class,
            'magazzino.auth' => \App\Http\Middleware\MagazzinoAuth::class,
        ]);
    })
    ->withExceptions(function ($exceptions) {
        $exceptions->renderable(function (\Illuminate\Session\TokenMismatchException $e, $request) {
            if ($request->expectsJson()) {
                return response()->json(['message' => 'Sessione scaduta. Ricarica la pagina.'], 419);
            }
            return redirect()->back()->withInput($request->except('_token', 'password'))
                ->with('warning', 'Sessione scaduta. Riprova.');
        });
    })
    ->create();