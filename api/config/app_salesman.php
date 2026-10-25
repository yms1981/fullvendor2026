<?php

return [

    /*
    |--------------------------------------------------------------------------
    | AppSalesman (legacy config)
    |--------------------------------------------------------------------------
    |
    | Las rutas /api/v2/app-salesman son públicas (sin token).
    | Esta clave queda solo por compatibilidad; no se usa en middleware.
    |
    */
    'api_key' => (string) env('APP_SALESMAN_API_KEY', ''),

];
