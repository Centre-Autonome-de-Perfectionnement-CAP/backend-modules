<?php

use App\Modules\WhatsApp\Http\Controllers\WhatsAppAdminController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Routes du module WhatsApp
|--------------------------------------------------------------------------
|
| Préfixe global : /api/whatsapp (voir WhatsAppServiceProvider::boot()).
|
| - /admin/*    : protégées par auth:sanctum, rôle 'admin' vérifié DANS
|                 le contrôleur (assertAdmin), pas de middleware dédié
|                 (aucun middleware "role:*" n'existe dans ce projet).
| - /internal/* : PAS de auth:sanctum (appelées par le Node, pas un
|                 utilisateur connecté), protégées par X-Api-Key.
|                 Le Node n'y accède qu'en loopback.
*/

Route::prefix('whatsapp')->group(function () {

    Route::middleware('auth:sanctum')->prefix('admin')->group(function () {
        Route::get('/status', [WhatsAppAdminController::class, 'status']);
        Route::delete('/session', [WhatsAppAdminController::class, 'destroySession']);
        Route::get('/messages', [WhatsAppAdminController::class, 'messages']);
        Route::get('/messages/modules', [WhatsAppAdminController::class, 'modules']);
        Route::post('/messages/{id}/retry', [WhatsAppAdminController::class, 'retryMessage']);
        Route::get('/stats', [WhatsAppAdminController::class, 'stats']);
    });

    Route::prefix('internal')->group(function () {
        Route::post('/webhook', [WhatsAppAdminController::class, 'webhookReceive']);
        Route::get('/files/{token}', [WhatsAppAdminController::class, 'serveInternalFile']);
    });
});
