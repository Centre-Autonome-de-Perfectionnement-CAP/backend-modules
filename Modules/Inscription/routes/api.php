<?php

use Illuminate\Support\Facades\Route;
use Modules\Inscription\Http\Controllers\InscriptionController;

Route::middleware(['auth:sanctum'])->prefix('v1')->group(function () {
    Route::apiResource('inscriptions', InscriptionController::class)->names('inscription');
});
