<?php

use App\Modules\Bibliotheque\Http\Controllers\LibraryPageController;
use Illuminate\Support\Facades\Route;

// Pages des travaux : page du site vitrine, balises de référencement écrites par le serveur.
Route::middleware(['web', 'throttle:120,1'])->group(function () {
    Route::get('travaux', [LibraryPageController::class, 'index']);
    Route::get('travaux/{slug}', [LibraryPageController::class, 'show'])->where('slug', '[a-z0-9-]+');
    Route::get('travaux/{slug}/pdf', [LibraryPageController::class, 'pdf'])->where('slug', '[a-z0-9-]+');
    Route::get('sitemap.xml', [LibraryPageController::class, 'sitemap']);
});
