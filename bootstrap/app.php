<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use App\Http\Middleware\OwnerMiddleware;

use App\Http\Middleware\OperatoreAuth;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function ($middleware) {
        $middleware->alias([
            'operatore.auth' => OperatoreAuth::class,
            'owner' => \App\Http\Middleware\OwnerMiddleware::class,
        ]);
    })
    ->withExceptions(function ($exceptions) {
        //
    })
    ->create();