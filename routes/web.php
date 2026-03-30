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

<<<<<<< HEAD
// Route principale
Route::get('/', fn() => returnIndexHtml());

// Route services SPA
Route::get('/services/{any?}', fn() => returnIndexHtml('services/index.html'))
    ->where('any', '^(?!.*\.(js|css|png|jpg|jpeg|gif|svg|ico|json|woff|woff2|ttf|eot|map)).*');

// Catch-all pour app-cap, excluant API et services
Route::get('/{any}', fn() => returnIndexHtml())
    ->where('any', '^(?!api/)(?!services/)(?!.*\.(js|css|png|jpg|jpeg|gif|svg|ico|json|woff|woff2|ttf|eot|map)).*');
=======
// Route pour app-cap-frontend - exclure les fichiers statiques
Route::get('/services/{any?}', function () {
    return file_get_contents(public_path('app-cap-frontend/index.html'));
})->where('any', '^(?!.*\.(js|css|png|jpg|jpeg|gif|svg|ico|json|woff|woff2|ttf|eot|map)).*');

// Route catch-all pour app-cap (doit être en dernier) - exclure les fichiers statiques et les routes API
Route::get('/{any}', function () {
    return file_get_contents(public_path('app-cap/index.html'));
})->where('any', '^(?!api/)(?!services/)(?!.*\.(js|css|png|jpg|jpeg|gif|svg|ico|json|woff|woff2|ttf|eot|map)).*');
>>>>>>> be0384f0d56cb4491eb015c3bc1466c68a041a8f
