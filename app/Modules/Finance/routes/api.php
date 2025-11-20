<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Modules\Finance\Http\Controllers\PaiementController;
use App\Modules\Finance\Http\Controllers\DashboardController;
use App\Modules\Finance\Http\Controllers\TarifController;
use App\Modules\Finance\Http\Controllers\HistoriqueController;
use App\Modules\Finance\Http\Controllers\ValidationController;

// Routes for Finance module

Route::prefix('api/finance')->group(function () {
    // Dashboard
    Route::get('/dashboard/stats', [DashboardController::class, 'getStats']);
    Route::get('/dashboard/pending-payments', [DashboardController::class, 'getPendingPayments']);
    
    // Paiements
    Route::get('/paiements', [PaiementController::class, 'index']);
    Route::post('/paiements', [PaiementController::class, 'store']);
    Route::get('/paiements/{reference}/download', [PaiementController::class, 'download'])->where('reference', '.*');
    Route::get('/paiements/{reference}', [PaiementController::class, 'show'])->where('reference', '.*');
    Route::get('/students/{matricule}', [PaiementController::class, 'getStudentInfo']);
    
    // Tarifs
    Route::get('/tarifs', [TarifController::class, 'index']);
    Route::post('/tarifs', [TarifController::class, 'store']);
    Route::put('/tarifs/{id}', [TarifController::class, 'update']);
    Route::delete('/tarifs/{id}', [TarifController::class, 'destroy']);
    
    // Historique
    Route::get('/historique/class', [HistoriqueController::class, 'getByClass']);
    Route::get('/historique/student/{studentId}', [HistoriqueController::class, 'getStudentFinancialState']);
    Route::get('/historique/export/class', [HistoriqueController::class, 'exportClassFinancialState']);
    
    // Validation des quittances
    Route::get('/validation/pending', [ValidationController::class, 'getPendingPayments']);
    Route::post('/validation/{paymentId}/validate', [ValidationController::class, 'validatePayment']);
    Route::post('/validation/{paymentId}/reject', [ValidationController::class, 'rejectPayment']);
    Route::get('/validation/{paymentId}/receipt', [ValidationController::class, 'downloadReceipt']);
});
