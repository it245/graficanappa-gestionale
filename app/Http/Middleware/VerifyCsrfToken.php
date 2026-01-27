<?php

namespace App\Http\Middleware;

use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken as Middleware;

class VerifyCsrfToken extends Middleware
{
    // Elenco delle rotte escluse dal CSRF
    protected $except = [
        'api/operatore/login',
    ];
}