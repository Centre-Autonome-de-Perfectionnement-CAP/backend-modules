<?php

use Illuminate\Support\Facades\Route;
use App\Modules\Auth\Http\Controllers\AuthController;
use App\Modules\Auth\Http\Controllers\AdministrationController;

Route::prefix('auth')->group(function () {
    Route::post('/login', [AuthController::class, 'login']);
    Route::post('/register', [AuthController::class, 'register']);

    Route::middleware('auth:sanctum')->group(function () {
        Route::post('/logout', [AuthController::class, 'logout']);
        Route::get('/me', [AuthController::class, 'me']);
    });

    // Route publique pour récupérer les membres// Routes d'administration
    Route::get('/administration', [AdministrationController::class, 'index']);
    Route::get('/soutien-informatique', [AdministrationController::class, 'soutienInformatique']);
});
