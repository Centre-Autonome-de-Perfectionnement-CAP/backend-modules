<?php

use Illuminate\Support\Facades\Route;
use App\Modules\Auth\Http\Controllers\AuthController;
use App\Modules\Auth\Http\Controllers\AdministrationController;

Route::prefix('api/auth')->group(function () {
    Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:10,1');
    Route::post('/register', [AuthController::class, 'register'])->middleware('throttle:5,1');
    Route::middleware('auth:sanctum')->group(function () {
        Route::post('/logout', [AuthController::class, 'logout']);
        Route::get('/me', [AuthController::class, 'me']);
    });
    Route::get('/administration', [AdministrationController::class, 'index']);
    Route::get('/soutien-informatique', [AdministrationController::class, 'soutienInformatique']);
});
