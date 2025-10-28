<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Modules\Finance\Http\Controllers\PaiementController;

// Routes for Finance module

// Routes publiques pour les paiements (non protégées par authentification)
Route::prefix('api/finance')->group(function () {
    // Liste des paiements avec recherche et filtres
    Route::get('/paiements', [PaiementController::class, 'index']);
    
    // Créer un nouveau paiement
    Route::post('/paiements', [PaiementController::class, 'store']);
    
    // Consulter le statut d'un paiement par référence
    Route::get('/paiements/{reference}', [PaiementController::class, 'show']);
    
    // Récupérer les infos d'un étudiant par matricule
    Route::get('/students/{matricule}', [PaiementController::class, 'getStudentInfo']);
});
