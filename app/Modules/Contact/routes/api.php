<?php

use App\Modules\Contact\Http\Controllers\ContactController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Contact Module API Routes
|--------------------------------------------------------------------------
*/

Route::prefix('contact')->group(function () {
    // Route publique pour soumettre un message de contact
    // Limitation anti-spam : 5 messages / minute / IP.
    Route::post('/contact', [ContactController::class, 'store'])
        ->middleware('throttle:5,1')
        ->name('contact.store');

    // Routes admin (nécessitent authentification)
    Route::middleware(['auth:sanctum'])->prefix('admin')->group(function () {
        Route::get('/contacts', [ContactController::class, 'index'])->name('contact.index');
        Route::get('/contacts/stats', [ContactController::class, 'stats'])->name('contact.stats');
        Route::get('/contacts/{contact}', [ContactController::class, 'show'])->name('contact.show');
        Route::put('/contacts/{contact}', [ContactController::class, 'update'])->name('contact.update');
        Route::delete('/contacts/{contact}', [ContactController::class, 'destroy'])->name('contact.destroy');
    });
});
