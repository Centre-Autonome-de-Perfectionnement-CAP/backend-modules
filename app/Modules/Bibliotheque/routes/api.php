<?php

use App\Modules\Bibliotheque\Http\Controllers\LibraryAdminController;
use App\Modules\Bibliotheque\Http\Controllers\LibraryPublicController;
use Illuminate\Support\Facades\Route;

// Progiciel : seul le responsable de division gère et publie.
Route::prefix('api/bibliotheque/admin')->middleware(['api', 'auth:sanctum', 'can:bibliotheque.manage'])->group(function () {
    Route::get('travaux', [LibraryAdminController::class, 'index']);
    Route::get('candidats', [LibraryAdminController::class, 'candidates']);
    Route::get('travaux/{id}', [LibraryAdminController::class, 'show'])->whereNumber('id');
    Route::get('travaux/{id}/fichier', [LibraryAdminController::class, 'file'])->whereNumber('id')->middleware('throttle:30,1');
    Route::middleware('throttle:30,1')->group(function () {
        Route::post('travaux', [LibraryAdminController::class, 'store']);
        Route::post('import', [LibraryAdminController::class, 'import']);
        Route::put('travaux/{id}', [LibraryAdminController::class, 'update'])->whereNumber('id');
        Route::post('travaux/{id}/fichier', [LibraryAdminController::class, 'replaceFile'])->whereNumber('id');
        Route::post('travaux/{id}/publier', [LibraryAdminController::class, 'publish'])->whereNumber('id');
        Route::post('travaux/{id}/retirer', [LibraryAdminController::class, 'unpublish'])->whereNumber('id');
        Route::post('travaux/{id}/nouveau-lien', [LibraryAdminController::class, 'renewLink'])->whereNumber('id');
        Route::delete('travaux/{id}', [LibraryAdminController::class, 'destroy'])->whereNumber('id');
    });
});

// Site vitrine : travaux publiés (et « par lien » avec la clé).
Route::prefix('api/travaux')->middleware(['api', 'throttle:120,1'])->group(function () {
    Route::get('/', [LibraryPublicController::class, 'index']);
    Route::get('{slug}', [LibraryPublicController::class, 'show'])->where('slug', '[a-z0-9-]+');
    Route::post('{slug}/contact', [LibraryPublicController::class, 'contact'])->where('slug', '[a-z0-9-]+')->middleware('throttle:bibliotheque-contact');
});
