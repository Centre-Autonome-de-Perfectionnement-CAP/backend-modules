<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

// Route globale de vérification d'adresse email en temps réel (DNS / Format)
Route::match(['get', 'post'], '/core/validate-email', function (\Illuminate\Http\Request $request) {
    $email = $request->input('email', '');
    $result = \App\Rules\ValidRealEmail::analyzeEmail((string) $email);
    return response()->json($result, $result['valid'] ? 200 : 422);
})->name('api.core.validate-email');

// Routes de validation d'email par code OTP à 6 chiffres
Route::post('/core/otp/send', [\App\Modules\Core\Http\Controllers\OtpVerificationController::class, 'sendOtp'])->name('api.core.otp.send');
Route::post('/core/otp/verify', [\App\Modules\Core\Http\Controllers\OtpVerificationController::class, 'verifyOtp'])->name('api.core.otp.verify');

