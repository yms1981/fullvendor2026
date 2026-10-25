<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web:      __DIR__ . '/../routes/web.php',
        api:      __DIR__ . '/../routes/api.php',
        commands: __DIR__ . '/../routes/console.php',
        health:   '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        //
        $middleware->alias(
            [
                'abilities' => \Laravel\Sanctum\Http\Middleware\CheckAbilities::class,
                'ability' => \Laravel\Sanctum\Http\Middleware\CheckForAnyAbility::class,
                'catalog.cart.token' => \App\Http\Middleware\ResolveCatalogCartToken::class,
                'seller.portal.access' => \App\Http\Middleware\EnsureSellerPortalAccess::class,
                'erp.api_key' => \App\Http\Middleware\ValidateErpApiKey::class,
            ]
        );

        // Resuelve el locale desde headers/query del cliente (MVC, app móvil).
        // Debe aplicarse globalmente porque Fortify registra /api/v2/login en el grupo `web`,
        // no en `api`, y queremos que `auth.failed` salga en el idioma del usuario.
        $middleware->append(\App\Http\Middleware\SetLocaleFromRequest::class);

        // Deshabilitar el throttle automático para las rutas API
        // En Laravel 11, el throttle se aplica automáticamente a las rutas API
        // Para deshabilitarlo, removemos el middleware ThrottleRequests del grupo API
        $middleware->api(remove: [
            \Illuminate\Routing\Middleware\ThrottleRequests::class
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();
