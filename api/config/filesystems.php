<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Default Filesystem Disk
    |--------------------------------------------------------------------------
    |
    | Here you may specify the default filesystem disk that should be used
    | by the framework. The "local" disk, as well as a variety of cloud
    | based disks are available to your application for file storage.
    |
    */

    'default' => env('FILESYSTEM_DISK', 'local'),

    'cloud' => env('FILESYSTEM_CLOUD', 's3'),

    /*
    |--------------------------------------------------------------------------
    | Product / category / company images disk
    |--------------------------------------------------------------------------
    |
    | Switch photo architecture without code changes:
    |   - minio         → object storage (S3-compatible MinIO)
    |   - images_local  → directory tree under IMAGES_LOCAL_ROOT
    |   - local / directory / dir → aliases of images_local
    |
    | Directory layout (images_local):
|   {IMAGES_LOCAL_ROOT}/products/{companyId}/{file}
|   {IMAGES_LOCAL_ROOT}/categories/{companyId}/{file}
|   {IMAGES_LOCAL_ROOT}/users/{file}
|   {IMAGES_LOCAL_ROOT}/chat/{companyId}/{conversationId}/{file}
    |
    */
    'images' => env('IMAGES_DISK', 'minio'),

    /*
    |--------------------------------------------------------------------------
    | Filesystem Disks
    |--------------------------------------------------------------------------
    |
    | Below you may configure as many filesystem disks as necessary, and you
    | may even configure multiple disks for the same driver. Examples for
    | most supported storage drivers are configured here for reference.
    |
    | Supported drivers: "local", "ftp", "sftp", "s3"
    |
    */

    'disks' => [

        'local' => [
            'driver' => 'local',
            'root' => storage_path('app'),
            'throw' => false,
        ],

        'public' => [
            'driver' => 'local',
            'root' => storage_path('app/public'),
            'url' => env('APP_URL').'/storage',
            'visibility' => 'public',
            'throw' => false,
        ],

        /*
         * Disco dedicado a fotos en filesystem (alternativa a MinIO).
         * Por defecto apunta al volumen MVC uploads (Docker: /var/www/mvc_uploads).
         */
        'images_local' => [
            'driver' => 'local',
            'root' => env(
                'IMAGES_LOCAL_ROOT',
                is_dir('/var/www/mvc_uploads')
                    ? '/var/www/mvc_uploads'
                    : dirname(base_path()).'/mvc/uploads'
            ),
            'visibility' => 'public',
            'throw' => false,
        ],

        'legacy' => [
            'driver' => 'local',
            'root' => env(
                'MVC_UPLOADS_PATH',
                is_dir('/var/www/mvc_uploads')
                    ? '/var/www/mvc_uploads'
                    : dirname(base_path()).'/mvc/uploads'
            ),
            'throw' => false,
        ],

        's3' => [
            'driver' => 's3',
            'key' => env('AWS_ACCESS_KEY_ID'),
            'secret' => env('AWS_SECRET_ACCESS_KEY'),
            'region' => env('AWS_DEFAULT_REGION'),
            'bucket' => env('AWS_BUCKET'),
            'url' => env('AWS_URL'),
            'endpoint' => env('AWS_ENDPOINT'),
            'use_path_style_endpoint' => env('AWS_USE_PATH_STYLE_ENDPOINT', false),
            'throw' => false,
        ],

        'minio' => [
            'driver' => 's3',
            'endpoint' => env('MINIO_ENDPOINT', 'http://minio:9000'),
            'use_path_style_endpoint' => env('AWS_USE_PATH_STYLE_ENDPOINT', true),
            'key' => env('AWS_ACCESS_KEY_ID', env('MINIO_ROOT_USER', 'sail')),
            'secret' => env('AWS_SECRET_ACCESS_KEY', env('MINIO_ROOT_PASSWORD', 'password')),
            'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
            'bucket' => env('AWS_BUCKET', 'images'),
            'throw' => false,
            'visibility' => 'public',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Symbolic Links
    |--------------------------------------------------------------------------
    |
    | Here you may configure the symbolic links that will be created when the
    | `storage:link` Artisan command is executed. The array keys should be
    | the locations of the links and the values should be their targets.
    |
    */

    'links' => [
        public_path('storage') => storage_path('app/public'),
    ],

];
