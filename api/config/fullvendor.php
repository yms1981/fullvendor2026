<?php

return [

    /*
    |--------------------------------------------------------------------------
    | URL pública del front (CodeIgniter)
    |--------------------------------------------------------------------------
    |
    | Donde responden rutas como /catalogo/producto. Debe coincidir con BASE_URL
    | del contenedor MVC. Si no se define FULLVENDOR_WEB_URL, se usa APP_URL
    | (puede apuntar solo a la API en algunos entornos: conviene definir la URL real).
    |
    */

    'web_url' => rtrim((string) env('FULLVENDOR_WEB_URL', env('APP_URL', 'http://localhost')), '/'),

];
