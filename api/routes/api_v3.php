<?php

/**
 * API v3 - Mobile App Interface
 * 
 * Esta versión está optimizada para aplicaciones móviles con:
 * - Respuestas optimizadas (menos datos, formato compacto)
 * - Autenticación mejorada con refresh tokens
 * - Endpoints específicos para sincronización offline
 * - Soporte para notificaciones push
 * - Optimización de imágenes y recursos
 */

use Illuminate\Support\Facades\Route;

Route::group(
    [
        'prefix' => 'v3',
        'middleware' => ['api']
    ],
    function () {
        
        // ============================================
        // ENDPOINTS PÚBLICOS (Sin autenticación)
        // ============================================
        
        // Autenticación
        Route::post('/auth/login', [\App\Http\Controllers\Mobile\AuthController::class, 'login'])
            ->name('mobile.auth.login');
        
        Route::post('/auth/register', [\App\Http\Controllers\Mobile\AuthController::class, 'register'])
            ->name('mobile.auth.register');
        
        Route::post('/auth/forgot-password', [\App\Http\Controllers\Mobile\AuthController::class, 'forgotPassword'])
            ->name('mobile.auth.forgot-password');
        
        Route::post('/auth/reset-password', [\App\Http\Controllers\Mobile\AuthController::class, 'resetPassword'])
            ->name('mobile.auth.reset-password');
        
        // Información pública
        Route::get('/app/info', [\App\Http\Controllers\Mobile\AppController::class, 'info'])
            ->name('mobile.app.info');
        
        Route::get('/app/version', [\App\Http\Controllers\Mobile\AppController::class, 'version'])
            ->name('mobile.app.version');
        
        // ============================================
        // ENDPOINTS AUTENTICADOS
        // ============================================
        
        Route::middleware('auth:sanctum')->group(function () {
            
            // Autenticación y perfil
            Route::prefix('auth')->group(function () {
                Route::get('/me', [\App\Http\Controllers\Mobile\AuthController::class, 'me'])
                    ->name('mobile.auth.me');
                
                Route::post('/refresh', [\App\Http\Controllers\Mobile\AuthController::class, 'refreshToken'])
                    ->name('mobile.auth.refresh');
                
                Route::post('/logout', [\App\Http\Controllers\Mobile\AuthController::class, 'logout'])
                    ->name('mobile.auth.logout');
                
                Route::put('/profile', [\App\Http\Controllers\Mobile\AuthController::class, 'updateProfile'])
                    ->name('mobile.auth.update-profile');
                
                Route::put('/password', [\App\Http\Controllers\Mobile\AuthController::class, 'updatePassword'])
                    ->name('mobile.auth.update-password');
            });
            
            // Dispositivo y notificaciones
            Route::prefix('device')->group(function () {
                Route::post('/register', [\App\Http\Controllers\Mobile\DeviceController::class, 'register'])
                    ->name('mobile.device.register');
                
                Route::delete('/unregister', [\App\Http\Controllers\Mobile\DeviceController::class, 'unregister'])
                    ->name('mobile.device.unregister');
                
                Route::put('/token', [\App\Http\Controllers\Mobile\DeviceController::class, 'updateToken'])
                    ->name('mobile.device.update-token');
            });

            // Sync móvil (salesman / genérico): registro + decisión total/parcial + deltas
            Route::post('/movilregister', [\App\Http\Controllers\Mobile\MovilSyncController::class, 'movilregister'])
                ->name('mobile.movilregister');
            Route::post('/movilsync', [\App\Http\Controllers\Mobile\MovilSyncController::class, 'movilsync'])
                ->name('mobile.movilsync');
            Route::post('/movilsync/complete', [\App\Http\Controllers\Mobile\MovilSyncController::class, 'complete'])
                ->name('mobile.movilsync.complete');
            Route::match(['get', 'post'], '/movilsync/products', [\App\Http\Controllers\Mobile\MovilSyncController::class, 'products'])
                ->name('mobile.movilsync.products');
            Route::match(['get', 'post'], '/movilsync/customers', [\App\Http\Controllers\Mobile\MovilSyncController::class, 'customers'])
                ->name('mobile.movilsync.customers');
            Route::match(['get', 'post'], '/movilsync/categories', [\App\Http\Controllers\Mobile\MovilSyncController::class, 'categories'])
                ->name('mobile.movilsync.categories');
            Route::match(['get', 'post'], '/movilsync/orders', [\App\Http\Controllers\Mobile\MovilSyncController::class, 'orders'])
                ->name('mobile.movilsync.orders');

            // Sync propio de FullVendor Warehouse (no usa rutas AppSalesman)
            Route::prefix('warehouse')->group(function () {
                Route::post('/movilregister', [\App\Http\Controllers\Warehouse\WarehouseSyncController::class, 'movilregister'])
                    ->name('warehouse.sync.movilregister');
                Route::post('/movilsync', [\App\Http\Controllers\Warehouse\WarehouseSyncController::class, 'movilsync'])
                    ->name('warehouse.sync.movilsync');
                Route::post('/movilsync/complete', [\App\Http\Controllers\Warehouse\WarehouseSyncController::class, 'complete'])
                    ->name('warehouse.sync.complete');
                Route::match(['get', 'post'], '/movilsync/products', [\App\Http\Controllers\Warehouse\WarehouseSyncController::class, 'products'])
                    ->name('warehouse.sync.products');
                Route::match(['get', 'post'], '/movilsync/customers', [\App\Http\Controllers\Warehouse\WarehouseSyncController::class, 'customers'])
                    ->name('warehouse.sync.customers');
                Route::match(['get', 'post'], '/movilsync/categories', [\App\Http\Controllers\Warehouse\WarehouseSyncController::class, 'categories'])
                    ->name('warehouse.sync.categories');
                Route::match(['get', 'post'], '/movilsync/orders', [\App\Http\Controllers\Warehouse\WarehouseSyncController::class, 'orders'])
                    ->name('warehouse.sync.orders');

                // API dedicada sync productos warehouse (estable; no usa catalog/products-all).
                Route::get('/products/sync', [\App\Http\Controllers\Warehouse\WarehouseProductsSyncController::class, 'sync'])
                    ->name('warehouse.products.sync');

                // Ventas / devoluciones últimos 6 meses (agrupadas) para notas de crédito offline.
                // Rutas conservan "last-year" por compatibilidad con la app; filtro = 6 meses.
                Route::get('/sales/last-year', [\App\Http\Controllers\Warehouse\WarehouseSalesReturnsController::class, 'salesLastYear'])
                    ->name('warehouse.sales.last-year');
                Route::get('/returns/last-year', [\App\Http\Controllers\Warehouse\WarehouseSalesReturnsController::class, 'returnsLastYear'])
                    ->name('warehouse.returns.last-year');

                // Catálogo paginado exclusivo warehouse (sync total). No toca product/all, customer/all, category/all.
                Route::prefix('catalog')->group(function () {
                    Route::get('/products', [\App\Http\Controllers\Warehouse\WarehouseCatalogController::class, 'products'])
                        ->name('warehouse.catalog.products');
                    // Todos los productos (enabled + disabled) — sync warehouse.
                    Route::get('/products-all', [\App\Http\Controllers\Warehouse\WarehouseCatalogController::class, 'productsAll'])
                        ->name('warehouse.catalog.products-all');
                    Route::get('/customers', [\App\Http\Controllers\Warehouse\WarehouseCatalogController::class, 'customers'])
                        ->name('warehouse.catalog.customers');
                    // Clientes con órdenes en el último año (notas de crédito / selección offline).
                    Route::get('/customers-with-orders', [\App\Http\Controllers\Warehouse\WarehouseCatalogController::class, 'customersWithOrders'])
                        ->name('warehouse.catalog.customers-with-orders');
                    Route::get('/categories', [\App\Http\Controllers\Warehouse\WarehouseCatalogController::class, 'categories'])
                        ->name('warehouse.catalog.categories');
                    // Categorías en orden del catálogo default (order_categories).
                    Route::get('/categories-ordered', [\App\Http\Controllers\Warehouse\WarehouseCatalogController::class, 'categoriesOrdered'])
                        ->name('warehouse.catalog.categories-ordered');
                });
            });
            
            // Compañías y catálogos
            Route::prefix('companies')->group(function () {
                Route::get('/', [\App\Http\Controllers\Mobile\CompanyController::class, 'index'])
                    ->name('mobile.companies.index');
                
                Route::get('/{companyId}', [\App\Http\Controllers\Mobile\CompanyController::class, 'show'])
                    ->name('mobile.companies.show');
                
                Route::get('/{companyId}/catalogs', [\App\Http\Controllers\Mobile\CompanyController::class, 'catalogs'])
                    ->name('mobile.companies.catalogs');
                
                Route::get('/{companyId}/catalog/{catalogId}', [\App\Http\Controllers\Mobile\CompanyController::class, 'catalog'])
                    ->name('mobile.companies.catalog');
            });
            
            // Categorías
            Route::prefix('categories')->group(function () {
                Route::get('/', [\App\Http\Controllers\Mobile\CategoryController::class, 'index'])
                    ->name('mobile.categories.index');
                
                Route::get('/{categoryId}', [\App\Http\Controllers\Mobile\CategoryController::class, 'show'])
                    ->name('mobile.categories.show');
                
                Route::get('/{categoryId}/products', [\App\Http\Controllers\Mobile\CategoryController::class, 'products'])
                    ->name('mobile.categories.products');
            });
            
            // Productos
            Route::prefix('products')->group(function () {
                Route::get('/', [\App\Http\Controllers\Mobile\ProductController::class, 'index'])
                    ->name('mobile.products.index');
                
                Route::get('/search', [\App\Http\Controllers\Mobile\ProductController::class, 'search'])
                    ->name('mobile.products.search');
                
                Route::get('/{productId}', [\App\Http\Controllers\Mobile\ProductController::class, 'show'])
                    ->name('mobile.products.show');
                
                Route::get('/{productId}/images', [\App\Http\Controllers\Mobile\ProductController::class, 'images'])
                    ->name('mobile.products.images');
            });
            
            // Carrito de compras
            Route::prefix('cart')->group(function () {
                Route::get('/', [\App\Http\Controllers\Mobile\CartController::class, 'index'])
                    ->name('mobile.cart.index');
                
                Route::get('/{cartId}', [\App\Http\Controllers\Mobile\CartController::class, 'show'])
                    ->name('mobile.cart.show');
                
                Route::post('/', [\App\Http\Controllers\Mobile\CartController::class, 'create'])
                    ->name('mobile.cart.create');
                
                Route::post('/items', [\App\Http\Controllers\Mobile\CartController::class, 'addItem'])
                    ->name('mobile.cart.add-item');
                
                Route::put('/items/{itemId}', [\App\Http\Controllers\Mobile\CartController::class, 'updateItem'])
                    ->name('mobile.cart.update-item');
                
                Route::delete('/items/{itemId}', [\App\Http\Controllers\Mobile\CartController::class, 'removeItem'])
                    ->name('mobile.cart.remove-item');
                
                Route::post('/{cartId}/checkout', [\App\Http\Controllers\Mobile\CartController::class, 'checkout'])
                    ->name('mobile.cart.checkout');
            });
            
            // Órdenes
            Route::prefix('orders')->group(function () {
                Route::get('/', [\App\Http\Controllers\Mobile\OrderController::class, 'index'])
                    ->name('mobile.orders.index');
                
                Route::get('/{orderId}', [\App\Http\Controllers\Mobile\OrderController::class, 'show'])
                    ->name('mobile.orders.show');
                
                Route::get('/{orderId}/details', [\App\Http\Controllers\Mobile\OrderController::class, 'details'])
                    ->name('mobile.orders.details');
                
                Route::get('/{orderId}/status', [\App\Http\Controllers\Mobile\OrderController::class, 'status'])
                    ->name('mobile.orders.status');
            });
            
            // Clientes
            Route::prefix('customers')->group(function () {
                Route::get('/', [\App\Http\Controllers\Mobile\CustomerController::class, 'index'])
                    ->name('mobile.customers.index');
                
                Route::get('/{customerId}', [\App\Http\Controllers\Mobile\CustomerController::class, 'show'])
                    ->name('mobile.customers.show');
            });
            
            // Sincronización offline
            Route::prefix('sync')->group(function () {
                Route::get('/status', [\App\Http\Controllers\Mobile\SyncController::class, 'status'])
                    ->name('mobile.sync.status');
                
                Route::post('/pull', [\App\Http\Controllers\Mobile\SyncController::class, 'pull'])
                    ->name('mobile.sync.pull');
                
                Route::post('/push', [\App\Http\Controllers\Mobile\SyncController::class, 'push'])
                    ->name('mobile.sync.push');
            });
            
            // Notificaciones
            Route::prefix('notifications')->group(function () {
                Route::get('/', [\App\Http\Controllers\Mobile\NotificationController::class, 'index'])
                    ->name('mobile.notifications.index');
                
                Route::get('/unread', [\App\Http\Controllers\Mobile\NotificationController::class, 'unread'])
                    ->name('mobile.notifications.unread');
                
                Route::put('/{notificationId}/read', [\App\Http\Controllers\Mobile\NotificationController::class, 'markAsRead'])
                    ->name('mobile.notifications.mark-read');
                
                Route::put('/read-all', [\App\Http\Controllers\Mobile\NotificationController::class, 'markAllAsRead'])
                    ->name('mobile.notifications.mark-all-read');
            });
        });
    }
);

