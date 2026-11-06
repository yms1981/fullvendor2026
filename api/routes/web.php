<?php

use App\Http\Controllers\Cron\ScheduleTickController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

/** Acceso corto a la UI Swagger (L5-Swagger). */
Route::redirect('/swagger', '/api/v2/documentation', 302);
Route::redirect('/api-docs', '/api/v2/documentation', 302);

// Scheduler HTTP (hosting cron → curl cada minuto). Ver SCHEDULE_TOKEN.
Route::match(['GET', 'POST'], '/cron/schedule', ScheduleTickController::class)
    ->name('cron.schedule');