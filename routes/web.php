<?php

use Illuminate\Support\Facades\Route;

// ========== FICHIERS DE STOCKAGE (avant les catch-all) ==========
//
// AJOUT (Benoite) — sert les fichiers de storage/app/public/ directement.
// N'entre jamais en collision avec le symlink standard public/storage
// (segment d'URL différent : "stockage", pas "storage").
//
// À VÉRIFIER : confirmer qu'un lien symbolique public/storage existe déjà
// (php artisan storage:link) ou non. Si non, cette route est le seul accès
// aux fichiers stockés et doit rester. Si oui, elle fait doublon — inoffensif
// mais à nettoyer plus tard si confirmé inutile.
Route::get('/stockage/{path}', function ($path) {
    $fullPath = storage_path('app/public/' . $path);

    if (!file_exists($fullPath)) {
        abort(404, 'Fichier non trouvé : ' . $path);
    }

    return response()->file($fullPath);
})->where('path', '.*');

// ========== APPLICATIONS FRONTEND ==========
//
// Structure réelle actuelle (vérifiée) :
//   public/app-cap/index.html          → site vitrine (racine)
//   public/app-cap-frontend/index.html → progiciel admin (préfixe /services)

// Route pour l'application principale app-cap (site vitrine à la racine)
Route::get('/', function () {
    return file_get_contents(public_path('app-cap/index.html'));
});

// Route pour app-cap-frontend (progiciel admin) - exclure les fichiers statiques
Route::get('/services/{any?}', function () {
    return file_get_contents(public_path('app-cap-frontend/index.html'));
})->where('any', '^(?!.*\.(js|css|png|jpg|jpeg|gif|svg|ico|json|woff|woff2|ttf|eot|map)).*');

// Route catch-all pour app-cap (doit être en dernier) - exclure les fichiers
// statiques, les routes API et le stockage
Route::get('/{any}', function () {
    return file_get_contents(public_path('app-cap/index.html'));
})->where('any', '^(?!api/)(?!services/)(?!stockage/)(?!.*\.(js|css|png|jpg|jpeg|gif|svg|ico|json|woff|woff2|ttf|eot|map)).*');
