<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Normalización al subir (productos, categorías, logos, usuarios, chat)
    |--------------------------------------------------------------------------
    |
    | Toda imagen se convierte al formato de almacenamiento, se baja calidad
    | y se reduce si supera el máximo. scaleDown conserva proporción; no agranda.
    | JPEG con calidad ~82 ocupa mucho menos que PNG lossless.
    |
    */

    'storage_extension' => env('IMAGES_STORAGE_EXTENSION', 'jpg'),
    'storage_mime' => env('IMAGES_STORAGE_MIME', 'image/jpeg'),
    'storage_quality' => (int) env('IMAGES_STORAGE_QUALITY', 82),

    'max_width' => (int) env('IMAGES_MAX_WIDTH', 1200),
    'max_height' => (int) env('IMAGES_MAX_HEIGHT', 1200),

    /*
    | Límites por tipo de subida (sobrescriben max_width/max_height).
    */
    'max_by_type' => [
        'product' => [
            'width' => (int) env('IMAGES_PRODUCT_MAX_WIDTH', env('IMAGES_MAX_WIDTH', 1200)),
            'height' => (int) env('IMAGES_PRODUCT_MAX_HEIGHT', env('IMAGES_MAX_HEIGHT', 1200)),
        ],
        'category' => [
            'width' => (int) env('IMAGES_CATEGORY_MAX_WIDTH', 1000),
            'height' => (int) env('IMAGES_CATEGORY_MAX_HEIGHT', 1000),
        ],
        'company' => [
            'width' => (int) env('IMAGES_COMPANY_MAX_WIDTH', 800),
            'height' => (int) env('IMAGES_COMPANY_MAX_HEIGHT', 800),
        ],
        'user' => [
            'width' => (int) env('IMAGES_USER_MAX_WIDTH', 512),
            'height' => (int) env('IMAGES_USER_MAX_HEIGHT', 512),
        ],
        'chat' => [
            'width' => (int) env('IMAGES_CHAT_MAX_WIDTH', 1280),
            'height' => (int) env('IMAGES_CHAT_MAX_HEIGHT', 1280),
        ],
    ],

    /*
    | Si true, falla la subida cuando no se pueda normalizar (no guarda el original grande).
    */
    'require_normalization' => filter_var(env('IMAGES_REQUIRE_NORMALIZATION', true), FILTER_VALIDATE_BOOL),
];
