<?php

use Illuminate\Support\Facades\Route;
use Modules\Inscription\Http\Controllers\InscriptionController;

Route::middleware(['auth', 'verified'])->group(function () {
    Route::resource('inscriptions', InscriptionController::class)->names('inscription');
});
