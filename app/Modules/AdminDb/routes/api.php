<?php

use Illuminate\Support\Facades\Route;
use App\Modules\AdminDb\Http\Controllers\AdminTableController;

/**
 * Routes de l'outil d'administration brute des tables.
 *
 * La whitelist des tables accessibles est gérée uniquement dans
 * AdminTableController::ALLOWED_TABLES — pas ici.
 *
 * Le rôle 'admin' est vérifié dans le contrôleur (assertAdmin), pas via un
 * middleware dédié, pour rester cohérent avec le reste du projet qui
 * contrôle les rôles au niveau contrôleur/policy (cf. DocumentRequestPolicy).
 */
Route::prefix('api/admin-db')->middleware('auth:sanctum')->group(function () {
    Route::get('tables',                 [AdminTableController::class, 'tables']);
    Route::get('tables/{table}',         [AdminTableController::class, 'show']);
    Route::post('tables/{table}',        [AdminTableController::class, 'store']);
    Route::put('tables/{table}/{id}',    [AdminTableController::class, 'update'])->whereNumber('id');
    Route::delete('tables/{table}/{id}', [AdminTableController::class, 'destroy'])->whereNumber('id');
});
