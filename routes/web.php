<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\DashboardController;

// Rotta di test rapido /health
Route::get('/health', function () {
    return 'MES OK';
});

// Rotta principale welcome
Route::get('/', function () {
    return view('welcome');
});

// Dashboard operatore
Route::get('/dashboard/operator', [DashboardController::class, 'operator'])
    ->name('dashboard.operator');

// Dashboard superadmin
Route::get('/dashboard/admin', [DashboardController::class, 'superadmin'])
    ->name('dashboard.superadmin');
