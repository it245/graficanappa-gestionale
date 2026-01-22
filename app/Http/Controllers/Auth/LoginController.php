<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Foundation\Auth\AuthenticatesUsers;

class LoginController extends Controller
{
    use AuthenticatesUsers;

    /**
     * Redirige l'utente dopo il login in base al ruolo
     */
    protected function redirectTo(): string
    {
        $user = auth()->user();

        if ($user->ruolo === 'owner') {
            return route('owner.dashboard');
        }

        return route('operatore.dashboard');
    }

    /**
     * Costruttore: solo guest può vedere login, logout solo utenti loggati
     */
    public function __construct()
    {
        $this->middleware('guest')->except('logout');
        $this->middleware('auth')->only('logout');
    }
}