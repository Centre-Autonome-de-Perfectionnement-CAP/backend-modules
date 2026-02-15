<?php

use Illuminate\Support\Facades\Route;

// Route pour l'application principale app-cap (site vitrine à la racine)
Route::get('/', function () {
    return file_get_contents(public_path('index.html'));
});

// Route pour app-cap-frontend (services) - exclure les fichiers statiques
Route::get('/services/{any?}', function () {
    return file_get_contents(public_path('app-cap-frontend/index.html'));
})->where('any', '^(?!.*\.(js|css|png|jpg|jpeg|gif|svg|ico|json|woff|woff2|ttf|eot|map)).*');

// Route catch-all pour app-cap (doit être en dernier) - exclure les fichiers statiques et les routes API
Route::get('/{any}', function () {
    return file_get_contents(public_path('app-cap/index.html'));
})->where('any', '^(?!api/)(?!services/)(?!.*\.(js|css|png|jpg|jpeg|gif|svg|ico|json|woff|woff2|ttf|eot|map)).*');
// Route pour l'API Laravel (si vous en avez)
// Route::get('/api/endpoint', [Controller::class, 'method']);


