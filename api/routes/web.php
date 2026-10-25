<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

/** Acceso corto a la UI Swagger (L5-Swagger). */
Route::redirect('/swagger', '/api/v2/documentation', 302);
Route::redirect('/api-docs', '/api/v2/documentation', 302);