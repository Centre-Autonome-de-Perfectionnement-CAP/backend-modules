<?php

use Illuminate\Support\Facades\Route;

// Route pour l'application principale app-cap (site vitrine)
Route::get('/app-cap/{any?}', function () {
    return file_get_contents(public_path('app-cap/index.html'));
})->where('any', '^(?!.*\.(js|css|png|jpg|jpeg|gif|svg|ico|json|woff|woff2|ttf|eot|map)).*');

// Route pour app-cap-frontend (application de gestion)
Route::get('/app-cap-frontend/{any?}', function () {
    return file_get_contents(public_path('app-cap-frontend/index.html'));
})->where('any', '^(?!.*\.(js|css|png|jpg|jpeg|gif|svg|ico|json|woff|woff2|ttf|eot|map)).*');
