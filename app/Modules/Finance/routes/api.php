<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Modules\Finance\Http\Controllers\PaiementController;

// Routes for Finance module

Route::prefix('api/finance')->group(function () {
    // Liste des paiements avec recherche et filtres
    Route::get('/paiements', [PaiementController::class, 'index']);
    
    // Créer un nouveau paiement
    Route::post('/paiements', [PaiementController::class, 'store']);
    
    // Télécharger la quittance d'un paiement (doit être avant la route show pour éviter les conflits)
    Route::get('/paiements/{reference}/download', [PaiementController::class, 'download'])->where('reference', '.*');
    
    // Consulter le statut d'un paiement par référence
    Route::get('/paiements/{reference}', [PaiementController::class, 'show'])->where('reference', '.*');
    
    // Récupérer les infos d'un étudiant par matricule
    Route::get('/students/{matricule}', [PaiementController::class, 'getStudentInfo']);
});
