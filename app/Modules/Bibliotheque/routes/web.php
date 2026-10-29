<?php

use App\Modules\Bibliotheque\Http\Controllers\LibraryPageController;
use Illuminate\Support\Facades\Route;

// Pages des travaux : page du site vitrine, balises de référencement écrites par le serveur.
Route::middleware(['web', 'throttle:120,1'])->group(function () {
    Route::get('travaux', [LibraryPageController::class, 'index']);
    Route::get('travaux/{id}', [LibraryPageController::class, 'show'])->whereNumber('id');
    Route::get('travaux/{id}/pdf', [LibraryPageController::class, 'pdf'])->whereNumber('id');
    // Anciennes adresses longues (titre dans l'adresse) : redirigées vers l'adresse courte.
    Route::get('travaux/{slug}', [LibraryPageController::class, 'legacy'])->where('slug', '[a-z0-9-]*[a-z][a-z0-9-]*');
    Route::get('sitemap.xml', [LibraryPageController::class, 'sitemap']);
});
