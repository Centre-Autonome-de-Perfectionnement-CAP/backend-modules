<?php

use Illuminate\Support\Facades\Route;
use Modules\GestionNotes\Http\Controllers\GestionNotesController;

Route::middleware(['auth', 'verified'])->group(function () {
    Route::resource('gestionnotes', GestionNotesController::class)->names('gestionnotes');
});
