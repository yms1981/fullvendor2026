<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Ecommerce API (header X-Api-Key)
    |--------------------------------------------------------------------------
    |
    | Token fijo de la tienda Home Value para /api/v2/ecommerce/*.
    | ECOMMERCE_COMPANY_ID > 0 limita las órdenes a esa compañía.
    |
    */
    'api_key' => (string) env('ECOMMERCE_API_KEY', ''),

    'company_id' => (int) env('ECOMMERCE_COMPANY_ID', 0),

];
