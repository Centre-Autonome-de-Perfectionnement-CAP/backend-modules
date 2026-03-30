<?php

use Illuminate\Support\Facades\Route;
use App\Modules\RH\Http\Controllers\ProfessorController;
use App\Modules\RH\Http\Controllers\AdminUserController;
use App\Modules\RH\Http\Controllers\GradeController;
use App\Modules\RH\Http\Controllers\SignataireController;
use App\Modules\RH\Http\Controllers\DocumentManagementController;
use App\Modules\RH\Http\Controllers\ImportantInformationController;
use App\Modules\RH\Http\Controllers\FileController;

use App\Modules\RH\Http\Controllers\WhatsAppGroupController;

Route::prefix('api/rh')->group(function () {
    // Routes publiques
    Route::get('important-informations', [ImportantInformationController::class, 'index']);
    Route::get('professors', [ProfessorController::class, 'index']);
    Route::get('grades', [GradeController::class, 'index']);
    Route::get('files/{file}', [FileController::class, 'viewDocument']);
    Route::get('documents', [DocumentManagementController::class, 'index']);
    
    // Route de debug temporaire
    Route::get('debug/file/{fileId}', function($fileId) {
        $file = \App\Modules\Stockage\Models\File::find($fileId);
        
        if (!$file) {
            return response()->json(['error' => 'Fichier non trouvé', 'file_id' => $fileId]);
        }
        
        $filePath = $file->file_path;
        if (str_starts_with($filePath, 'public/')) {
            $filePath = substr($filePath, 7);
        }
        
        $exists = \Storage::disk($file->disk)->exists($filePath);
        $fullPath = \Storage::disk($file->disk)->path($filePath);
        
        return response()->json([
            'file_id' => $fileId,
            'file_path' => $file->file_path,
            'processed_path' => $filePath,
            'disk' => $file->disk,
            'full_system_path' => $fullPath,
            'exists' => $exists,
            'is_official_document' => $file->is_official_document,
            'original_name' => $file->original_name
        ]);
    });
    
    Route::middleware('auth:sanctum')->group(function () {
        // Gestion des groupes WhatsApp
        Route::get('whatsapp-groups', [WhatsAppGroupController::class, 'index']);
        Route::put('whatsapp-groups/{department}', [WhatsAppGroupController::class, 'update']);
        Route::delete('whatsapp-groups/{department}', [WhatsAppGroupController::class, 'destroy']);
        
        // Gestion des documents
        Route::apiResource('documents', DocumentManagementController::class)->except(['index']);
        
        // Gestion des informations importantes
        Route::get('important-informations/admin', [ImportantInformationController::class, 'indexAdmin']);
        Route::post('important-informations/{important_information}/broadcast', [ImportantInformationController::class, 'broadcast']);
        Route::get('broadcast-status/{broadcastId}', [ImportantInformationController::class, 'getBroadcastStatus']);
        Route::apiResource('important-informations', ImportantInformationController::class)->except(['index']);
        
        // CRUD Professeurs (sauf index qui est public)
        Route::apiResource('professors', ProfessorController::class)->only(['store', 'show', 'update', 'destroy']);
        
        Route::apiResource('admin-users', AdminUserController::class);
        Route::apiResource('signataires', SignataireController::class);

        Route::post('admin-users/{adminUser}/roles/attach', [AdminUserController::class, 'attachRole']);
        Route::post('admin-users/{adminUser}/roles/detach', [AdminUserController::class, 'detachRole']);
        Route::get('admin-users-statistics', [AdminUserController::class, 'statistics']);
        Route::get('banks', [ProfessorController::class, 'getBanks']);
        
        Route::get('roles', function () {
            return response()->json([
                'success' => true,
                'data' => \App\Modules\Stockage\Models\Role::select('id', 'name', 'slug')->get(),
            ]);
        });
    });

}); 