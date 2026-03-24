<?php

use Illuminate\Support\Facades\Route;

function returnIndexHtml($path = 'index.html') {
    $file = public_path($path);
    if (file_exists($file)) {
        return response()->file($file, [
            'Content-Type' => 'text/html'
        ]);
    }
    abort(404, 'Fichier introuvable : '.$path);
}

// Route principale
Route::get('/', fn() => returnIndexHtml());

// Route services SPA
Route::get('/services/{any?}', fn() => returnIndexHtml('services/index.html'))
    ->where('any', '^(?!.*\.(js|css|png|jpg|jpeg|gif|svg|ico|json|woff|woff2|ttf|eot|map)).*');

// Catch-all pour app-cap, excluant API et services
Route::get('/{any}', fn() => returnIndexHtml())
    ->where('any', '^(?!api/)(?!services/)(?!.*\.(js|css|png|jpg|jpeg|gif|svg|ico|json|woff|woff2|ttf|eot|map)).*');
