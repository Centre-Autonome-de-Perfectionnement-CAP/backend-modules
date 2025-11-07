<?php

use Illuminate\Support\Facades\Route;

// Route pour l'application principale app-cap
Route::get('/', function () {
    return file_get_contents(public_path('app-cap/index.html'));
});

// Route pour app-cap-frontend
Route::get('/frontend/{any?}', function () {
    return file_get_contents(public_path('app-cap-frontend/index.html'));
})->where('any', '.*');

// Route pour l'API Laravel (si vous en avez)
// Route::get('/api/endpoint', [Controller::class, 'method']);

// Route catch-all pour app-cap (doit être en dernier)
Route::get('/{any}', function () {
    return file_get_contents(public_path('app-cap/index.html'));
})->where('any', '.*');
