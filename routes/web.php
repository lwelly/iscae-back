<?php
// routes/web.php

use Illuminate\Support\Facades\Route;

// ============================================================
// Page d'accueil — Redirection vers la SPA Vue.js
// ============================================================
Route::get('/', function () {
    return response()->json([
        'app'     => config('app.name'),
        'version' => 'v1.0.0',
        'docs'    => url('/api/v1/health'),
        'status'  => 'running',
    ]);
})->name('home');

// ============================================================
// Route catch-all pour la SPA Vue.js (frontend)
// Toutes les routes non-API renvoient vers le SPA
// ============================================================
Route::get('/{any}', function () {
    // Si le fichier index.html existe (production build)
    $indexPath = public_path('index.html');

    if (file_exists($indexPath)) {
        return response()->file($indexPath);
    }

    // En développement : message d'orientation
    return response()->json([
        'message' => 'Frontend Vue.js non compilé. Lancez : npm run build',
        'api_url' => url('/api/v1'),
    ], 200);
})->where('any', '^(?!api).*$')->name('spa');
