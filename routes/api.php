<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
 Route::get('/test', function () {
    return 'Ceci est une route de test';
});
});
