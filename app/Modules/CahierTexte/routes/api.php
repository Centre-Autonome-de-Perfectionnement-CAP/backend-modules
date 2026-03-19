<?php

use Illuminate\Support\Facades\Route;
use App\Modules\CahierTexte\Http\Controllers\TextbookEntryController;
use App\Modules\CahierTexte\Http\Controllers\TextbookCommentController;

Route::middleware(['auth:sanctum'])->prefix('cahier-texte')->group(function () {
    
    // Textbook Entries
    Route::get('/', [TextbookEntryController::class, 'index']);
    Route::post('/', [TextbookEntryController::class, 'store']);
    Route::get('/{id}', [TextbookEntryController::class, 'show']);
    Route::put('/{id}', [TextbookEntryController::class, 'update']);
    Route::patch('/{id}', [TextbookEntryController::class, 'update']);
    Route::delete('/{id}', [TextbookEntryController::class, 'destroy']);
    
    // Actions spéciales
    Route::post('/{id}/publish', [TextbookEntryController::class, 'publish']);
    Route::post('/{id}/validate', [TextbookEntryController::class, 'validateEntry']);
    
    // Vues par entité
    Route::get('/class-group/{classGroupId}', [TextbookEntryController::class, 'byClassGroup']);
    Route::get('/professor/{professorId}', [TextbookEntryController::class, 'byProfessor']);
    
    // Statistiques
    Route::get('/statistics/all', [TextbookEntryController::class, 'statistics']);
    
    // Comments
    Route::get('/{entryId}/comments', [TextbookCommentController::class, 'index']);
    Route::post('/{entryId}/comments', [TextbookCommentController::class, 'store']);
    Route::put('/{entryId}/comments/{commentId}', [TextbookCommentController::class, 'update']);
    Route::delete('/{entryId}/comments/{commentId}', [TextbookCommentController::class, 'destroy']);
});
