<?php

use App\Modules\Bibliotheque\Http\Controllers\LibraryPageController;
use Illuminate\Support\Facades\Route;

// Pages publiques des travaux (rendues par le serveur, référençables).
Route::middleware(['web', 'throttle:120,1'])->group(function () {
    Route::get('travaux', [LibraryPageController::class, 'index'])->name('travaux.index');
    Route::get('travaux/{slug}', [LibraryPageController::class, 'show'])->where('slug', '[a-z0-9-]+')->name('travaux.show');
    Route::get('travaux/{slug}/pdf', [LibraryPageController::class, 'pdf'])->where('slug', '[a-z0-9-]+')->name('travaux.pdf');
    Route::post('travaux/{slug}/contact', [LibraryPageController::class, 'contact'])->where('slug', '[a-z0-9-]+')
        ->middleware('throttle:bibliotheque-contact')->name('travaux.contact');
    Route::get('sitemap.xml', [LibraryPageController::class, 'sitemap']);
});
