<?php

use App\Console\Commands\StripeCreateMissingInvoices;
use App\Console\Commands\StripeSyncPendingCompanies;
use App\Console\Commands\StripeSyncPendingPlans;
use App\Console\Commands\StripeSyncPendingSubscriptions;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote')->hourly();

Schedule::command(StripeSyncPendingCompanies::class)
    ->hourlyAt(0)
    ->withoutOverlapping()
    ->onOneServer()
    ->runInBackground();

Schedule::command(StripeSyncPendingPlans::class)
    ->hourlyAt(5)
    ->withoutOverlapping()
    ->onOneServer()
    ->runInBackground();

Schedule::command(StripeSyncPendingSubscriptions::class)
    ->hourlyAt(10)
    ->withoutOverlapping()
    ->onOneServer()
    ->runInBackground();

Schedule::command(StripeCreateMissingInvoices::class)
    ->hourlyAt(15)
    ->withoutOverlapping()
    ->onOneServer()
    ->runInBackground();

// Sync (no runInBackground): el tick HTTP /api/cron/schedule debe esperar el trabajo.
// onOneServer exige cache/Redis compartido; en XAMPP local bloquea o no dispara.
Schedule::command(\App\Console\Commands\RunErpScheduledTasksCommand::class)
    ->everyMinute()
    ->withoutOverlapping(5);

// Cola de fotos ERP: descarga en el mismo proceso del tick (cada minuto).
Schedule::command(\App\Console\Commands\ProcessErpProductImageQueueCommand::class, ['--limit' => 12])
    ->everyMinute()
    ->withoutOverlapping(5);
