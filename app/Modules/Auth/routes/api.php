<?php

use Illuminate\Support\Facades\Route;
use App\Modules\Auth\Http\Controllers\AuthController;
use App\Modules\Auth\Http\Controllers\AdministrationController;

// FUSION develop/main :
//   - préfixe 'auth' (convention develop : le préfixe 'api' et le middleware
//     'api' sont appliqués par AuthServiceProvider) ;
//   - durcissement sécurité de main : login limité à 10 tentatives / minute,
//     création de compte (/register) réservée aux utilisateurs authentifiés
//     du back-office.
Route::prefix('auth')->group(function () {
    Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:10,1');
    Route::middleware('auth:sanctum')->group(function () {
        Route::post('/register', [AuthController::class, 'register']);
        Route::post('/logout', [AuthController::class, 'logout']);
        Route::get('/me', [AuthController::class, 'me']);
    });
    Route::get('/administration', [AdministrationController::class, 'index']);
    Route::get('/soutien-informatique', [AdministrationController::class, 'soutienInformatique']);
});
