<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Modules\Finance\Http\Controllers\PaiementController;
use App\Modules\Finance\Http\Controllers\DashboardController;
use App\Modules\Finance\Http\Controllers\TarifController;
use App\Modules\Finance\Http\Controllers\HistoriqueController;
use App\Modules\Finance\Http\Controllers\ValidationController;
<<<<<<< HEAD
use App\Modules\Finance\Http\Controllers\TransactionController;
use App\Modules\Finance\Http\Controllers\ExonerationController;
use App\Modules\Finance\Http\Controllers\StudentFinanceController;
use App\Modules\Finance\Http\Controllers\ReportController;
use App\Modules\Finance\Http\Controllers\AcademicLevelFeeController;
=======
>>>>>>> eea2b06 (draft)

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
<<<<<<< HEAD
    Route::get('/tarifs/available-classes', [TarifController::class, 'getAvailableClasses']);
=======
>>>>>>> eea2b06 (draft)
    Route::post('/tarifs', [TarifController::class, 'store']);
    Route::put('/tarifs/{id}', [TarifController::class, 'update']);
    Route::delete('/tarifs/{id}', [TarifController::class, 'destroy']);
    
<<<<<<< HEAD
    // Tarifs par niveau académique
    Route::get('/academic-level-fees', [AcademicLevelFeeController::class, 'index']);
    Route::post('/academic-level-fees', [AcademicLevelFeeController::class, 'store']);
    Route::post('/academic-level-fees/bulk', [AcademicLevelFeeController::class, 'storeBulk']);
    Route::put('/academic-level-fees/{uuid}', [AcademicLevelFeeController::class, 'update']);
    Route::delete('/academic-level-fees/{uuid}', [AcademicLevelFeeController::class, 'destroy']);
    Route::post('/academic-level-fees/student-fee', [AcademicLevelFeeController::class, 'getStudentFee']);
    
=======
>>>>>>> eea2b06 (draft)
    // Historique
    Route::get('/historique/class', [HistoriqueController::class, 'getByClass']);
    Route::get('/historique/student/{studentId}', [HistoriqueController::class, 'getStudentFinancialState']);
    Route::get('/historique/export/class', [HistoriqueController::class, 'exportClassFinancialState']);
    
    // Validation des quittances
    Route::get('/validation/pending', [ValidationController::class, 'getPendingPayments']);
    Route::post('/validation/{paymentId}/validate', [ValidationController::class, 'validatePayment']);
    Route::post('/validation/{paymentId}/reject', [ValidationController::class, 'rejectPayment']);
    Route::get('/validation/{paymentId}/receipt', [ValidationController::class, 'downloadReceipt']);
<<<<<<< HEAD
    
    // Transactions
    Route::get('/transactions/student/{studentPendingStudentId}', [TransactionController::class, 'getStudentTransactions']);
    Route::get('/transactions/student/{studentPendingStudentId}/balance', [TransactionController::class, 'getStudentBalance']);
    
    // Exonérations
    Route::get('/exonerations', [ExonerationController::class, 'index']);
    Route::post('/exonerations', [ExonerationController::class, 'store']);
    Route::put('/exonerations/{id}', [ExonerationController::class, 'update']);
    Route::delete('/exonerations/{id}', [ExonerationController::class, 'destroy']);
    
    // État financier étudiant
    Route::get('/student/{studentPendingStudentId}/financial-state', [StudentFinanceController::class, 'getFinancialState']);
    Route::post('/student/submit-payment', [StudentFinanceController::class, 'submitPayment']);
    
    // Rapports et exports
    Route::get('/reports/export-payments', [ReportController::class, 'exportPayments']);
    Route::get('/reports/stats-by-department', [ReportController::class, 'getFinancialStatsByDepartment']);
    Route::get('/reports/revenue-by-period', [ReportController::class, 'getRevenueByPeriod']);
=======
>>>>>>> eea2b06 (draft)
});
