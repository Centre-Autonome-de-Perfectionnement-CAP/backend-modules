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

// ========== ROUTES STATIQUES (fichiers stockés) - DOIVENT ÊTRE AVANT ==========
Route::get('/stockage/{path}', function ($path) {
    $fullPath = storage_path('app/public/' . $path);

    if (!file_exists($fullPath)) {
        abort(404, 'Fichier non trouvé : ' . $path);
    }

    return response()->file($fullPath);
})->where('path', '.*');

// ========== ROUTES API ==========
// (Vos routes API ici...)

// ========== ROUTES PRINCIPALES ==========
Route::get('/', fn() => returnIndexHtml());

// Route pour services
Route::get('/services/{any?}', fn() => returnIndexHtml('services/index.html'))
    ->where('any', '^(?!.*\.(js|css|png|jpg|jpeg|gif|svg|ico|json|woff|woff2|ttf|eot|map)).*');

// Route pour app-cap-frontend
Route::get('/app-cap-frontend/{any?}', function () {
    $path = public_path('app-cap-frontend/index.html');
    if (file_exists($path)) {
        return file_get_contents($path);
    }
    abort(404, 'Fichier app-cap-frontend/index.html non trouvé');
})->where('any', '^(?!.*\.(js|css|png|jpg|jpeg|gif|svg|ico|json|woff|woff2|ttf|eot|map)).*');

// ========== CATCH-ALL (DOIT ÊTRE EN DERNIER) ==========
Route::get('/{any}', fn() => returnIndexHtml())
    ->where('any', '^(?!api/)(?!services/)(?!app-cap-frontend/)(?!stockage/)(?!.*\.(js|css|png|jpg|jpeg|gif|svg|ico|json|woff|woff2|ttf|eot|map)).*');