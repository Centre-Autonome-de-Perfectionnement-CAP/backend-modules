<?php

use Illuminate\Support\Facades\Route;
use App\Modules\RH\Http\Controllers\ProfessorController;
use App\Modules\RH\Http\Controllers\AdminUserController;
use App\Modules\RH\Http\Controllers\GradeController;

Route::prefix('api/rh')->group(function () {

// Routes protégées par authentification
Route::middleware('auth:sanctum')->group(function () {
    // CRUD Professeurs
    Route::apiResource('professors', ProfessorController::class);
    
    // CRUD Utilisateurs administratifs
    Route::apiResource('admin-users', AdminUserController::class);
    
    // Gestion des rôles pour les utilisateurs
    Route::post('admin-users/{adminUser}/roles/attach', [AdminUserController::class, 'attachRole']);
    Route::post('admin-users/{adminUser}/roles/detach', [AdminUserController::class, 'detachRole']);
    
    // Statistiques des utilisateurs
    Route::get('admin-users-statistics', [AdminUserController::class, 'statistics']);
    
    // Liste des grades
    Route::get('grades', [GradeController::class, 'index']);
    
    // Liste des banques
    Route::get('banks', [ProfessorController::class, 'getBanks']);
    
    // Liste des rôles
    Route::get('roles', function () {
        return response()->json([
            'success' => true,
            'data' => \App\Modules\Stockage\Models\Role::select('id', 'name', 'slug')->get(),
        ]);
    });
});

}); // Fin du groupe api/rh
