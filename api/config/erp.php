<?php

return [

    /*
    |--------------------------------------------------------------------------
    | ERP API key (header X-Api-Key)
    |--------------------------------------------------------------------------
    |
    | Token fijo para la sección /api/v2/erp/*. Configurable por entorno.
    |
    */
    'api_key' => (string) env('ERP_API_KEY', 'f008aa014308059be6c1c20ed70a006e'),

];
