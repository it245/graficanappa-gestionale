<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\OperatoreLoginController;

// Rotta API per login operatore
Route::post('operatore/login', [OperatoreLoginController::class,'login']);