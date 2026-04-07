<?php

use Illuminate\Support\Facades\Route;
use App\Modules\Stockage\Http\Controllers\FileController;
use App\Modules\Stockage\Http\Controllers\FilePermissionController;
use App\Modules\Stockage\Http\Controllers\FileShareController;
use App\Modules\Stockage\Http\Controllers\DocumentController;

/*
|--------------------------------------------------------------------------
| API Routes - Module Stockage
|--------------------------------------------------------------------------
|
| Routes pour la gestion des fichiers, permissions et partages
|
*/

Route::prefix('stockage')->group(function () {

// Routes publiques (fichiers publics et partages)
Route::prefix('files')->group(function () {
    // Fichiers publics
    Route::get('public', [FileController::class, 'publicFiles'])->name('api.files.public');
    
    // Accès aux partages (sans authentification)
    Route::prefix('share')->group(function () {
        Route::get('{token}', [FileShareController::class, 'access'])->name('api.files.share.access');
        Route::get('{token}/download', [FileShareController::class, 'download'])->name('api.files.share.download');
    });
});

// Routes protégées (authentification requise)
Route::middleware('auth:sanctum')->prefix('files')->group(function () {
    
    // Gestion des fichiers
    Route::get('/', [FileController::class, 'index'])->name('api.files.index');
    Route::post('/', [FileController::class, 'store'])->name('api.files.store');
    Route::get('{file}', [FileController::class, 'show'])->name('api.files.show');
    Route::put('{file}', [FileController::class, 'update'])->name('api.files.update');
    Route::delete('{file}', [FileController::class, 'destroy'])->name('api.files.destroy');
    
    // Actions sur les fichiers
    Route::get('{file}/download', [FileController::class, 'download'])->name('api.files.download');
    Route::get('{file}/view', [FileController::class, 'view'])->name('api.files.view');
    Route::post('{file}/visibility', [FileController::class, 'changeVisibility'])->name('api.files.visibility');
    Route::post('{file}/lock', [FileController::class, 'lock'])->name('api.files.lock');
    Route::post('{file}/unlock', [FileController::class, 'unlock'])->name('api.files.unlock');
    Route::get('{file}/activities', [FileController::class, 'activities'])->name('api.files.activities');
    
    // Gestion des permissions
    Route::prefix('{file}/permissions')->group(function () {
        Route::get('/', [FilePermissionController::class, 'index'])->name('api.files.permissions.index');
        Route::post('grant', [FilePermissionController::class, 'grant'])->name('api.files.permissions.grant');
        Route::post('revoke', [FilePermissionController::class, 'revoke'])->name('api.files.permissions.revoke');
        Route::post('check', [FilePermissionController::class, 'check'])->name('api.files.permissions.check');
    });
    
    // Gestion des partages
    Route::prefix('{file}/shares')->group(function () {
        Route::get('/', [FileShareController::class, 'index'])->name('api.files.shares.index');
        Route::post('/', [FileShareController::class, 'store'])->name('api.files.shares.store');
        Route::get('{share}', [FileShareController::class, 'show'])->name('api.files.shares.show');
        Route::post('{share}/deactivate', [FileShareController::class, 'deactivate'])->name('api.files.shares.deactivate');
        Route::delete('{share}', [FileShareController::class, 'destroy'])->name('api.files.shares.destroy');
    });
});

// Routes Documents (public GET, protected CUD)
Route::prefix('documents')->group(function () {
    // Routes publiques
    Route::get('/', [DocumentController::class, 'index'])->name('api.documents.index');
    Route::get('{document}', [DocumentController::class, 'show'])->name('api.documents.show');
    
    // Routes protégées (authentification requise)
    Route::middleware('auth:sanctum')->group(function () {
        Route::post('/', [DocumentController::class, 'store'])->name('api.documents.store');
        Route::put('{document}', [DocumentController::class, 'update'])->name('api.documents.update');
        Route::delete('{document}', [DocumentController::class, 'destroy'])->name('api.documents.destroy');
    });
});

}); // Fin du groupe api/stockage
