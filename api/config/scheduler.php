<?php

return [

    /*
    |--------------------------------------------------------------------------
    | HTTP scheduler tick (hosting cron → curl)
    |--------------------------------------------------------------------------
    |
    | Same pattern as fullvendorERP: GET/POST {APP_URL}/cron/schedule?token=…
    | Runs `php artisan schedule:run` (ERP scheduled tasks + image queue, etc.).
    |
    */
    'token' => (string) env('SCHEDULE_TOKEN', ''),

];
