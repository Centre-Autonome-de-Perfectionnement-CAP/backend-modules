<?php

use Illuminate\Support\Facades\Route;
use App\Modules\Core\Http\Controllers\OtpController;

Route::prefix('api/core')->group(function () {
    Route::prefix('otp')->middleware('throttle:10,1')->group(function () {
        Route::post('/send', [OtpController::class, 'send']);
        Route::post('/verify', [OtpController::class, 'verify']);
    });
});
