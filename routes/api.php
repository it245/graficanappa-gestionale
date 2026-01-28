<?php


use App\Http\Controllers\OperatoreLoginController;
use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;
use App\Models\Operatore;
use App\Models\OperatoreToken;

// Rotta API per login operatore
Route::post('operatore/login', [OperatoreLoginController::class,'login']);