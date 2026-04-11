<?php

use Illuminate\Support\Facades\Route;

/**
 * 1. ROUTES API
 * Elles sont gérées dans routes/api.php, donc rien à faire ici.
 */

/**
 * 2. ROUTES DES SERVICES (app-cap-frontend)
 * Si l'URL commence par /services, on renvoie vers le frontend spécifique.
 */
Route::get('/services/{any?}', function () {
    // Vérifiez si vous avez un fichier index.html dans public/services/
    $path = public_path('services/index.html');
    if (file_exists($path)) {
        return response()->file($path);
    }
    abort(404, "Le frontend 'services' n'est pas compilé.");
})->where('any', '.*');

/**
 * 3. ROUTE PRINCIPALE / CATCH-ALL (app-cap)
 * Pour toutes les autres routes (ex: /dashboard, /login), 
 * on renvoie vers l'application principale.
 */
Route::get('/{any?}', function () {
    // Si vous utilisez Vite, il est préférable de retourner une vue Blade :
    // return view('app'); 
    
    // Si vous insistez pour charger le HTML statique directement :
    $path = public_path('index.html');
    if (file_exists($path)) {
        return response()->file($path);
    }
    abort(404, "L'application principale n'est pas compilée.");
})->where('any', '^(?!api).*'); // On exclut juste l'API