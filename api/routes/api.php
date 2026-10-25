<?php

// Incluir rutas v3 para app móvil
require __DIR__ . '/api_v3.php';

Route::group(
    [
        'prefix' => 'v2'

    ],
    function () {
        Route::get('/', function () {
            redirect("documentation");
        });
        Route::get('/t/{string}', [\App\Http\Controllers\TranslationController::class, 't']);
        Route::get('/langmdl/{string}', [\App\Http\Controllers\TranslationController::class, 'langmdl']);
        Route::get('/langmodules/', [\App\Http\Controllers\TranslationController::class, 'modules']);

        Route::get('user/token', [\App\Http\Controllers\UserController::class, 'token']);
        // Público: slug (about-us) o pageId numérico. No usar {Cm}: el binding busca por PK y rompe los slugs.
        Route::get('cm/{cm}', [\App\Http\Controllers\CmController::class, 'show'])
            ->where('cm', '[A-Za-z0-9._\\-]+');
        
        // Public registration endpoint
        Route::post('register', [\App\Http\Controllers\AuthController::class, 'register']);

        Route::post('auth/refresh-mvc-token', [\App\Http\Controllers\AuthController::class, 'refreshMvcToken'])
            ->middleware('throttle:60,1');

        // Recuperar contraseña (flujo público del MVC: forgetpassword / reset-password)
        Route::post('auth/password/email-status', [\App\Http\Controllers\AuthController::class, 'passwordEmailStatus'])
            ->middleware('throttle:30,1')
            ->name('auth.password.emailStatus');
        Route::post('auth/password/reset', [\App\Http\Controllers\AuthController::class, 'resetPassword'])
            ->middleware('throttle:30,1')
            ->name('auth.password.reset');
        
        // Public customer creation endpoint (no authentication required)
        Route::post('customer/add', [\App\Http\Controllers\CustomerController::class, 'add'])->name('customer.add.public');

        // Public ERP order import (no authentication required)
        Route::post('order/import', [\App\Http\Controllers\OrderImportPublicController::class, 'import'])
            ->middleware('throttle:60,1')
            ->name('order.import.public');
        Route::post('order/import-bulk', [\App\Http\Controllers\OrderImportPublicController::class, 'importBulk'])
            ->middleware('throttle:30,1')
            ->name('order.import.bulk.public');

        // Public product stock / price update (no authentication required)
        Route::post('product/stock-update', [\App\Http\Controllers\ProductStockPublicController::class, 'update'])
            ->middleware('throttle:60,1')
            ->name('product.stock-update.public');

        // Sección ERP: autenticación por header X-Api-Key (token fijo, sin Sanctum)
        Route::prefix('erp')
            ->middleware(['erp.api_key', 'throttle:120,1'])
            ->group(function () {
                Route::get('ping', [\App\Http\Controllers\Erp\ErpController::class, 'ping'])
                    ->name('erp.ping');
            });

        // Sección AppSalesman: pública (sin token / sin Sanctum), solo throttle
        Route::prefix('app-salesman')
            ->middleware(['throttle:120,1'])
            ->group(function () {
                Route::get('ping', [\App\Http\Controllers\AppSalesman\AppSalesmanController::class, 'ping'])
                    ->name('app-salesman.ping');
                Route::post('orderList', [\App\Http\Controllers\AppSalesman\AppSalesmanController::class, 'orderList'])
                    ->name('app-salesman.orderList');
                Route::post('orderListCompany', [\App\Http\Controllers\AppSalesman\AppSalesmanController::class, 'orderListCompany'])
                    ->name('app-salesman.orderListCompany');
                Route::post('orderListSeller', [\App\Http\Controllers\AppSalesman\AppSalesmanController::class, 'orderListSeller'])
                    ->name('app-salesman.orderListSeller');
                Route::post('requested', [\App\Http\Controllers\AppSalesman\AppSalesmanController::class, 'requested'])
                    ->name('app-salesman.requested');
                Route::post('productFifo', [\App\Http\Controllers\AppSalesman\AppSalesmanController::class, 'productFifo'])
                    ->name('app-salesman.productFifo');
                Route::post('productFifoSummary', [\App\Http\Controllers\AppSalesman\AppSalesmanController::class, 'productFifoSummary'])
                    ->name('app-salesman.productFifoSummary');
                Route::post('catalogLink', [\App\Http\Controllers\AppSalesman\AppSalesmanController::class, 'catalogLink'])
                    ->name('app-salesman.catalogLink');
            });

        Route::post('customer/login', [\App\Http\Controllers\CustomerPortalLoginController::class, 'login'])
            ->middleware('throttle:30,1')
            ->name('customer.portal.login');

        Route::post('seller/login', [\App\Http\Controllers\SellerPortalLoginController::class, 'login'])
            ->middleware('throttle:30,1')
            ->name('seller.portal.login');

        // Confirmar / rechazar orden desde enlace del correo (products/order_confirm|reject/{token})
        Route::post('order/email-token/{token}/confirm', [\App\Http\Controllers\OrderEmailTokenController::class, 'confirm'])
            ->middleware('throttle:60,1')
            ->name('order.email-token.confirm');
        Route::post('order/email-token/{token}/reject', [\App\Http\Controllers\OrderEmailTokenController::class, 'reject'])
            ->middleware('throttle:60,1')
            ->name('order.email-token.reject');

        Route::prefix('marketplace')
            ->middleware('throttle:120,1')
            ->group(function () {
                Route::get('home', [\App\Http\Controllers\MarketplacePublicController::class, 'home'])
                    ->name('marketplace.home');
                Route::get('companies', [\App\Http\Controllers\MarketplacePublicController::class, 'companies'])
                    ->name('marketplace.companies');
                Route::match(['get', 'post'], 'companies/search', [\App\Http\Controllers\MarketplacePublicController::class, 'searchCompanies'])
                    ->name('marketplace.companies.search');
                Route::get('companies/slug/{slug}', [\App\Http\Controllers\MarketplacePublicController::class, 'companyBySlug'])
                    ->name('marketplace.companies.slug');
                Route::get('companies/{companyId}/categories', [\App\Http\Controllers\MarketplacePublicController::class, 'categories'])
                    ->whereNumber('companyId')
                    ->name('marketplace.companies.categories');
                Route::get('companies/{companyId}/products', [\App\Http\Controllers\MarketplacePublicController::class, 'products'])
                    ->whereNumber('companyId')
                    ->name('marketplace.companies.products');
                Route::post('products/by-category', [\App\Http\Controllers\MarketplacePublicController::class, 'productsByCategory'])
                    ->name('marketplace.products.byCategory');

                Route::post('customers/lookup', [\App\Http\Controllers\MarketplaceCustomerController::class, 'lookupByEmail'])
                    ->name('marketplace.customers.lookup');
                Route::post('customers/sign-in', [\App\Http\Controllers\MarketplaceCustomerController::class, 'signIn'])
                    ->name('marketplace.customers.signIn');
                Route::post('customers/register-password', [\App\Http\Controllers\MarketplaceCustomerController::class, 'registerPassword'])
                    ->name('marketplace.customers.registerPassword');
                Route::post('customers/reset-password', [\App\Http\Controllers\MarketplaceCustomerController::class, 'resetPassword'])
                    ->name('marketplace.customers.resetPassword');
                Route::get('customers/company/{companyId}', [\App\Http\Controllers\MarketplaceCustomerController::class, 'company'])
                    ->whereNumber('companyId')
                    ->name('marketplace.customers.company');
                Route::get('customers/company/{companyId}/customer/{customerId}', [\App\Http\Controllers\MarketplaceCustomerController::class, 'customer'])
                    ->whereNumber('companyId')
                    ->whereNumber('customerId')
                    ->name('marketplace.customers.customer');
            });

        Route::post('admin/platform-login', [\App\Http\Controllers\AdminPlatformLoginController::class, 'login'])
            ->middleware('throttle:30,1')
            ->name('admin.platform.login');

        Route::post('admin/password/email-status', [\App\Http\Controllers\AdminAccountController::class, 'passwordEmailStatus'])
            ->middleware('throttle:30,1')
            ->name('admin.password.emailStatus');
        Route::post('admin/password/reset', [\App\Http\Controllers\AdminAccountController::class, 'resetPassword'])
            ->middleware('throttle:30,1')
            ->name('admin.password.reset');

        Route::get('setting/public/branding', [\App\Http\Controllers\SettingController::class, 'publicBranding'])
            ->name('setting.public.branding');

        // Imágenes de catálogo desde MinIO (token de carrito en query, sin auth)
        Route::get('images/catalog/{image}.{imageType}', [\App\Http\Controllers\ImageController::class, 'imageForCatalog'])
            ->name('images.catalog');

        // Catálogo público MVC (catalogo/producto?token=…) — sin Bearer, auth por token_kor
        Route::prefix('catalog-public')
            ->middleware(['throttle:120,1', 'catalog.cart.token'])
            ->group(function () {
                Route::get('session', [\App\Http\Controllers\CatalogPublicController::class, 'session'])
                    ->name('catalog-public.session');
                Route::get('totals', [\App\Http\Controllers\CatalogPublicController::class, 'totals'])
                    ->name('catalog-public.totals');
                Route::get('lines', [\App\Http\Controllers\CatalogPublicController::class, 'lines'])
                    ->name('catalog-public.lines');
                Route::patch('session', [\App\Http\Controllers\CatalogPublicController::class, 'patchSession'])
                    ->name('catalog-public.patchSession');
                Route::get('products', [\App\Http\Controllers\CatalogPublicController::class, 'products'])
                    ->name('catalog-public.products');
                Route::get('products/count', [\App\Http\Controllers\CatalogPublicController::class, 'productCount'])
                    ->name('catalog-public.products.count');
                Route::get('products/{productId}', [\App\Http\Controllers\CatalogPublicController::class, 'productDetail'])
                    ->where('productId', '[0-9]+')
                    ->name('catalog-public.products.detail');
                Route::get('categories-order', [\App\Http\Controllers\CatalogPublicController::class, 'categoriesOrder'])
                    ->name('catalog-public.categories-order');
                Route::get('categories', [\App\Http\Controllers\CatalogPublicController::class, 'categoriesList'])
                    ->name('catalog-public.categories');
                Route::put('lines', [\App\Http\Controllers\CatalogPublicController::class, 'upsertLine'])
                    ->name('catalog-public.lines.upsert');
                Route::post('lines/add', [\App\Http\Controllers\CatalogPublicController::class, 'addLine'])
                    ->name('catalog-public.lines.add');
                Route::delete('lines/{productId}', [\App\Http\Controllers\CatalogPublicController::class, 'removeLine'])
                    ->where('productId', '[0-9]+')
                    ->name('catalog-public.lines.remove');
                Route::delete('lines', [\App\Http\Controllers\CatalogPublicController::class, 'clearLines'])
                    ->name('catalog-public.lines.clear');
                Route::post('place-order', [\App\Http\Controllers\CatalogPublicController::class, 'placeOrder'])
                    ->name('catalog-public.place-order');
                Route::get('orders', [\App\Http\Controllers\CatalogPublicController::class, 'orders'])
                    ->name('catalog-public.orders');
                Route::get('orders/{orderId}', [\App\Http\Controllers\CatalogPublicController::class, 'orderDetail'])
                    ->where('orderId', '[0-9]+')
                    ->name('catalog-public.orders.detail');
                Route::get('multi-cart/active-token', [\App\Http\Controllers\CatalogPublicController::class, 'multiCartActiveToken'])
                    ->name('catalog-public.multi-cart.active-token');
            });

        $imageUuidRoutePattern = '[0-9a-fA-F]{8}-[0-9a-fA-F]{4}-[0-9a-fA-F]{4}-[0-9a-fA-F]{4}-[0-9a-fA-F]{12}';
        $imageTypeRoutePattern = '[a-zA-Z0-9]+';

        // Fondo blanco → transparente (PNG). Antes de rutas con _{width}.
        Route::get('images/{image}_nobg.png', [\App\Http\Controllers\ImageController::class, 'imageNoBackground'])
            ->where(['image' => $imageUuidRoutePattern])
            ->name('images.showNoBackground');
        Route::get('images/{image}_{width}_nobg.png', [\App\Http\Controllers\ImageController::class, 'imageNoBackground'])
            ->where([
                'image' => $imageUuidRoutePattern,
                'width' => '[0-9]+',
            ])
            ->name('images.showNoBackgroundScaled');

        // Escalada pública (p. ej. uuid_90.png): debe ir ANTES que images/{uuid}.{ext} para no confundir uuid_90 con un UUID.
        Route::get('images/{image}_{width}.{imageType}', [\App\Http\Controllers\ImageController::class, 'imageScaledByWith'])
            ->where([
                'image' => $imageUuidRoutePattern,
                'width' => '[0-9]+',
                'imageType' => $imageTypeRoutePattern,
            ])
            ->name('images.showScaledByUuidPublic');

        // Escalada sin extensión: usa el formato real del registro (jpg/png/gif/webp/…).
        Route::get('images/{image}_{width}', [\App\Http\Controllers\ImageController::class, 'imageScaledByWidth'])
            ->where([
                'image' => $imageUuidRoutePattern,
                'width' => '[0-9]+',
            ])
            ->name('images.showScaledByUuidAutoPublic');

        // Placeholders / marcas (públicos: el proxy MVC las pide sin Bearer).
        Route::get('images/no-image', [\App\Http\Controllers\ImageController::class, 'noImage'])
            ->name('images.noImage');
        Route::get('images/logo.{ext}', [\App\Http\Controllers\ImageController::class, 'logo'])
            ->name('images.logo');
        Route::get('images/isologo.{ext}', [\App\Http\Controllers\ImageController::class, 'isologo'])
            ->name('images.isologo');

        // MinIO por UUID sin extensión: usa extension del registro (evita forzar .webp).
        // Importante: where UUID para que uuid.webp NO caiga aquí y use la ruta con .{imageType}.
        Route::get('images/{image}', [\App\Http\Controllers\ImageController::class, 'imageByUuid'])
            ->where([
                'image' => $imageUuidRoutePattern,
            ])
            ->name('images.showByUuidOnlyPublic');

        // MinIO por UUID: las vistas MVC usan <img src="..."> sin cabecera Bearer; debe ser pública (solo UUID).
        Route::get('images/{image}.{imageType}', [\App\Http\Controllers\ImageController::class, 'image'])
            ->where([
                'image' => $imageUuidRoutePattern,
                'imageType' => $imageTypeRoutePattern,
            ])
            ->name('images.showByUuidPublic');

        Route::middleware('auth:sanctum')->group(
            function () {
                Route::get('/user', function (Illuminate\Http\Request $request) {
                    return $request->user();
                });

                // Cambiar contraseña (panel company autenticado)
                Route::post('auth/password/change', [\App\Http\Controllers\AuthController::class, 'changePassword'])
                    ->name('auth.password.change');

                Route::prefix('seller-portal')
                    ->middleware('seller.portal.access')
                    ->name('seller-portal.')
                    ->group(function () {
                        Route::get('context', [\App\Http\Controllers\SellerPortalController::class, 'context'])
                            ->name('context');
                        Route::get('form-masters/customer-groups', [\App\Http\Controllers\SellerPortalController::class, 'customerGroups'])
                            ->name('form-masters.customer-groups');
                        Route::get('form-masters/terms', [\App\Http\Controllers\SellerPortalController::class, 'terms'])
                            ->name('form-masters.terms');
                        Route::get('form-masters/sellers', [\App\Http\Controllers\SellerPortalController::class, 'sellers'])
                            ->name('form-masters.sellers');
                        Route::get('form-masters/countries', [\App\Http\Controllers\SellerPortalController::class, 'countries'])
                            ->name('form-masters.countries');
                        Route::get('customers', [\App\Http\Controllers\SellerPortalController::class, 'customers'])
                            ->name('customers.index');
                        Route::get('customers/{customerId}', [\App\Http\Controllers\SellerPortalController::class, 'showCustomer'])
                            ->whereNumber('customerId')
                            ->name('customers.show');
                        Route::post('customers', [\App\Http\Controllers\SellerPortalController::class, 'storeCustomer'])
                            ->name('customers.store');
                        Route::put('customers/{customerId}', [\App\Http\Controllers\SellerPortalController::class, 'updateCustomer'])
                            ->whereNumber('customerId')
                            ->name('customers.update');
                        Route::post('customers/{customerId}/unassign', [\App\Http\Controllers\SellerPortalController::class, 'unassignCustomer'])
                            ->whereNumber('customerId')
                            ->name('customers.unassign');
                        Route::get('orders', [\App\Http\Controllers\SellerPortalController::class, 'orders'])
                            ->name('orders.index');
                        Route::get('orders/{orderId}', [\App\Http\Controllers\SellerPortalController::class, 'showOrder'])
                            ->whereNumber('orderId')
                            ->name('orders.show');
                        Route::get('products/{proId}', [\App\Http\Controllers\SellerPortalController::class, 'showProduct'])
                            ->whereNumber('proId')
                            ->name('products.show');
                        Route::get('catalog/customers/{customerId}/products', [\App\Http\Controllers\SellerPortalController::class, 'catalogProducts'])
                            ->whereNumber('customerId')
                            ->name('catalog.products');
                        Route::post('catalog/customers/{customerId}/browse-session', [\App\Http\Controllers\SellerPortalController::class, 'catalogBrowseSession'])
                            ->whereNumber('customerId')
                            ->name('catalog.browse-session');
                        Route::post('catalog/orders', [\App\Http\Controllers\SellerPortalController::class, 'storeCatalogOrder'])
                            ->name('catalog.orders.store');
                    });

                Route::get('admin/capabilities', [\App\Http\Controllers\AdminCapabilitiesController::class, 'show'])
                    ->name('admin.capabilities.show');
                Route::get('admin/account', [\App\Http\Controllers\AdminAccountController::class, 'show'])
                    ->name('admin.account.show');
                Route::put('admin/account', [\App\Http\Controllers\AdminAccountController::class, 'update'])
                    ->name('admin.account.update');
                Route::post('admin/password/change', [\App\Http\Controllers\AdminAccountController::class, 'changePassword'])
                    ->name('admin.password.change');

                Route::prefix('company-chat')->name('company-chat.')->group(function () {
                    Route::get('contacts', [\App\Http\Controllers\CompanyChatController::class, 'contacts'])
                        ->name('contacts');
                    Route::get('conversations', [\App\Http\Controllers\CompanyChatController::class, 'conversations'])
                        ->name('conversations.index');
                    Route::post('conversations', [\App\Http\Controllers\CompanyChatController::class, 'storeConversation'])
                        ->name('conversations.store');
                    Route::get('conversations/{conversationId}/messages', [\App\Http\Controllers\CompanyChatController::class, 'messages'])
                        ->whereNumber('conversationId')
                        ->name('messages.index');
                    Route::post('conversations/{conversationId}/messages', [\App\Http\Controllers\CompanyChatController::class, 'storeMessage'])
                        ->whereNumber('conversationId')
                        ->name('messages.store');
                    Route::post('conversations/{conversationId}/read', [\App\Http\Controllers\CompanyChatController::class, 'markRead'])
                        ->whereNumber('conversationId')
                        ->name('conversations.read');
                    Route::get('unread-count', [\App\Http\Controllers\CompanyChatController::class, 'unreadCount'])
                        ->name('unread-count');
                    Route::get('attachments/{attachmentId}', [\App\Http\Controllers\CompanyChatController::class, 'attachment'])
                        ->whereNumber('attachmentId')
                        ->name('attachments.show');
                });

                Route::prefix('assistant')->name('assistant.')->group(function () {
                    Route::get('status', [\App\Http\Controllers\AssistantChatController::class, 'status'])
                        ->name('status');
                    Route::post('chat', [\App\Http\Controllers\AssistantChatController::class, 'chat'])
                        ->name('chat');
                });

                Route::prefix('company-gps')->name('company-gps.')->group(function () {
                    Route::get('sellers', [\App\Http\Controllers\CompanyGpsTrackerController::class, 'sellers'])
                        ->name('sellers');
                    Route::get('track', [\App\Http\Controllers\CompanyGpsTrackerController::class, 'track'])
                        ->name('track');
                    Route::post('points', [\App\Http\Controllers\CompanyGpsTrackerController::class, 'storePoints'])
                        ->name('points.store');
                });

                Route::prefix('stripe')->name('stripe.')->group(function () {
                    Route::post('invoices/sync-all', [\App\Http\Controllers\StripeInvoiceController::class, 'syncAll'])
                        ->name('invoices.sync-all');
                    Route::post('invoices/create-missing', [\App\Http\Controllers\StripeInvoiceController::class, 'createMissing'])
                        ->name('invoices.create-missing');
                    Route::post('invoices/{invoice}/create', [\App\Http\Controllers\StripeInvoiceController::class, 'createOnStripe'])
                        ->whereNumber('invoice')
                        ->name('invoices.create');
                    Route::post('invoices/{invoice}/sync', [\App\Http\Controllers\StripeInvoiceController::class, 'syncFromStripe'])
                        ->whereNumber('invoice')
                        ->name('invoices.sync');
                    Route::get('invoices/stripe/{stripeInvoiceId}', [\App\Http\Controllers\StripeInvoiceController::class, 'showStripeInvoice'])
                        ->name('invoices.stripe.show');
                    Route::post('invoices/stripe/{stripeInvoiceId}/void', [\App\Http\Controllers\StripeInvoiceController::class, 'voidStripeInvoice'])
                        ->name('invoices.stripe.void');
                    Route::get('companies/{company}/invoices', [\App\Http\Controllers\StripeInvoiceController::class, 'listByCompany'])
                        ->whereNumber('company')
                        ->name('companies.invoices');

                    Route::post('companies/sync-pending-customers', [\App\Http\Controllers\StripeCustomerController::class, 'syncAllPending'])
                        ->name('companies.sync-pending-customers');
                    Route::post('companies/{company}/sync-customer', [\App\Http\Controllers\StripeCustomerController::class, 'sync'])
                        ->whereNumber('company')
                        ->name('companies.sync-customer');
                    Route::post('companies/{company}/refresh-customer', [\App\Http\Controllers\StripeCustomerController::class, 'refresh'])
                        ->whereNumber('company')
                        ->name('companies.refresh-customer');
                    Route::get('companies/{company}/customer', [\App\Http\Controllers\StripeCustomerController::class, 'show'])
                        ->whereNumber('company')
                        ->name('companies.customer.show');

                    Route::get('companies/{company}/payment-methods', [\App\Http\Controllers\StripePaymentMethodController::class, 'index'])
                        ->whereNumber('company')
                        ->name('companies.payment-methods.index');
                    Route::post('companies/{company}/payment-methods', [\App\Http\Controllers\StripePaymentMethodController::class, 'attach'])
                        ->whereNumber('company')
                        ->name('companies.payment-methods.attach');
                    Route::post('companies/{company}/payment-methods/default', [\App\Http\Controllers\StripePaymentMethodController::class, 'setDefault'])
                        ->whereNumber('company')
                        ->name('companies.payment-methods.default');
                    Route::delete('payment-methods/{paymentMethodId}', [\App\Http\Controllers\StripePaymentMethodController::class, 'detach'])
                        ->name('payment-methods.detach');

                    Route::post('plans/{plan}/sync-catalog', [\App\Http\Controllers\StripePlanController::class, 'syncCatalog'])
                        ->whereNumber('plan')
                        ->name('plans.sync-catalog');

                    Route::post('subscriptions/sync-all', [\App\Http\Controllers\StripeSubscriptionController::class, 'syncAll'])
                        ->name('subscriptions.sync-all');
                    Route::post('subscriptions/{subscription}/create', [\App\Http\Controllers\StripeSubscriptionController::class, 'createOnStripe'])
                        ->whereNumber('subscription')
                        ->name('subscriptions.create');
                    Route::post('subscriptions/{subscription}/sync', [\App\Http\Controllers\StripeSubscriptionController::class, 'syncFromStripe'])
                        ->whereNumber('subscription')
                        ->name('subscriptions.sync');
                    Route::post('subscriptions/{subscription}/cancel', [\App\Http\Controllers\StripeSubscriptionController::class, 'cancel'])
                        ->whereNumber('subscription')
                        ->name('subscriptions.cancel');

                    Route::get('documents', [\App\Http\Controllers\StripeDocumentController::class, 'index'])
                        ->name('documents.index');
                    Route::post('documents', [\App\Http\Controllers\StripeDocumentController::class, 'store'])
                        ->name('documents.store');
                    Route::put('documents/{document}', [\App\Http\Controllers\StripeDocumentController::class, 'update'])
                        ->whereNumber('document')
                        ->name('documents.update');
                    Route::delete('documents/{document}', [\App\Http\Controllers\StripeDocumentController::class, 'destroy'])
                        ->whereNumber('document')
                        ->name('documents.destroy');
                });

                Route::group(['prefix' => 'catalog'], function () {
                    Route::get(
                        '/{catalogId}/categories',
                        [\App\Http\Controllers\Company\CatalogController::class, 'categories']
                    )->name('catalog.categories');
                    Route::put(
                        '/{catalogId}/categories',
                        [\App\Http\Controllers\Company\CatalogController::class, 'syncCategories']
                    )->name('catalog.syncCategories');
                    Route::get(
                        '/{catalogId}/download-products',
                        [\App\Http\Controllers\Company\CatalogController::class, 'downloadProducts']
                    )->name('catalog.downloadProducts');
                    Route::get(
                        '/company/{companyId}/customer/{customerId}/price-list-export',
                        [\App\Http\Controllers\Company\CatalogController::class, 'priceListExport']
                    )->name('catalog.price-list-export');
                    Route::get(
                        '/{catalogId}/customer/{customerId}/products',
                        [\App\Http\Controllers\Company\CatalogController::class, 'products']
                    )->name('catalog.products');
                    Route::post(
                        '/send',
                        [\App\Http\Controllers\Company\CatalogController::class, 'sendMailCatalog']
                    )->name('catalog.sendMailCatalog');
                    Route::put(
                        '{catalogId}/products',
                        [\App\Http\Controllers\Company\CatalogController::class, 'assignProducts']
                    )
                        ->name('catalog.assignProducts');
                    Route::delete(
                        '{catalogId}/products',
                        [\App\Http\Controllers\Company\CatalogController::class, 'removeProducts']
                    )
                        ->name('catalog.removeProducts');
                });

                Route::group(['prefix' => 'catalogcart'], function () {
                    Route::post(
                        '/addProduct',
                        [\App\Http\Controllers\Company\CatalogCartController::class, 'addProduct']
                    )->name('catalogcart.addProduct');
                    Route::get(
                        '/company/{companyId}/cartIds',
                        [\App\Http\Controllers\Company\CatalogCartController::class, 'cartIds']
                    )->name('catalogcart.company.cartIds');
                    Route::get(
                        '/company/{companyId}/abandoned',
                        [\App\Http\Controllers\Company\CatalogCartController::class, 'abandonedCarts']
                    )->name('catalogcart.company.abandoned');
                    Route::get(
                        '/company/{companyId}/cart/{cartId}/open-detail',
                        [\App\Http\Controllers\Company\CatalogCartController::class, 'openCartDetail']
                    )->name('catalogcart.company.openDetail');
                    Route::get(
                        '/company/{companyId}/cart/{cartId}/soft-delete',
                        [\App\Http\Controllers\Company\CatalogCartController::class, 'openCartSoftDelete']
                    )->name('catalogcart.company.softDelete');
                    Route::post(
                        '/company/{companyId}/cart/{cartId}/submit',
                        [\App\Http\Controllers\Company\CatalogCartController::class, 'openCartSubmit']
                    )->name('catalogcart.company.submit');
                    Route::post(
                        '/company/{companyId}/cart/{cartId}/renew',
                        [\App\Http\Controllers\Company\CatalogCartController::class, 'openCartRenew']
                    )->name('catalogcart.company.renew');
                    Route::post(
                        '/company/{companyId}/openCart/sync',
                        [\App\Http\Controllers\Company\CatalogCartController::class, 'syncOpenCart']
                    )->name('catalogcart.company.openCartSync');
                    Route::get(
                        '/company/{companyId}/openCarts',
                        [\App\Http\Controllers\Company\CatalogCartController::class, 'openCarts']
                    )->name('catalogcart.company.openCarts');
                    Route::get(
                        '/company/{companyId}/customer/{customerId}',
                        [\App\Http\Controllers\Company\CatalogCartController::class, 'openCart']
                    )->name('catalogcart.customer.openCart');
                    Route::get(
                        '/company/{companyId}/customer/{customerId}/hasPendings',
                        [\App\Http\Controllers\Company\CatalogCartController::class, 'hasPendings']
                    )->name('catalogcart.hasPendings');
                    Route::get(
                        '/company/{companyId}/customer/{customerId}/expired-count',
                        [\App\Http\Controllers\Company\CatalogCartController::class, 'expiredCatalogCartsCount']
                    )->name('catalogcart.company.expiredCount');
                    Route::get(
                        '/company/{companyId}/dashboard/send-catalog-count',
                        [\App\Http\Controllers\Company\CatalogCartController::class, 'sendCatalogCount']
                    )->name('catalogcart.company.sendCatalogCount');
                    Route::post(
                        '/legacy-token',
                        [\App\Http\Controllers\Company\CatalogCartController::class, 'generateLegacyToken']
                    )->name('catalogcart.legacyToken');
                    Route::get(
                        '/company/{companyId}/product/{productId}/open-cart-lines',
                        [\App\Http\Controllers\Company\CatalogCartController::class, 'productOpenCartLines']
                    )->name('catalogcart.company.productOpenCartLines');
                    Route::get(
                        '/token-{token}',
                        [\App\Http\Controllers\Company\CatalogCartController::class, 'getByToken']
                    )->name('catalogcart.getByToken');
                    Route::delete(
                        '/{catalogCartId}/details',
                        [\App\Http\Controllers\Company\CatalogCartController::class, 'destroyDetails']
                    )->name('catalogcart.destroyDetails');
                    Route::delete(
                        '/{orderId}/delete-product/{productId}',
                        [\App\Http\Controllers\Company\CatalogCartController::class, 'deleteProduct']
                    )->name('catalogcart.deleteProduct');
                    Route::get(
                        '{orderId}/info',
                        [\App\Http\Controllers\Company\CatalogCartController::class, 'info']
                    )->name('detailcar.order.info');
                    Route::put(
                        '{order}/place-order',
                        [\App\Http\Controllers\Company\CatalogCartController::class, 'placeOrder']
                    )->name('detailcar.place-order');
                    Route::post(
                        '/quickAddProduct',
                        [\App\Http\Controllers\Company\CatalogCartController::class, 'quickAddProduct']
                    )->name('catalogcart.quickAddProduct');
                });

                Route::group(['prefix' => 'category'], function () {
                    Route::get(
                        '/all',
                        [\App\Http\Controllers\CategoryController::class, 'all']
                    )->name('category.all');
                    Route::get(
                        '/catalog',
                        [\App\Http\Controllers\CategoryController::class, 'catalog']
                    )->name('category.catalog');
                    Route::put(
                        '/{categoryId}/enable',
                        [\App\Http\Controllers\CategoryController::class, 'enable']
                    )->name('category.enable');
                    Route::put(
                        '/{categoryId}/disable',
                        [\App\Http\Controllers\CategoryController::class, 'disable']
                    )->name('category.disable');
                    Route::post(
                        '/export',
                        [\App\Http\Controllers\CategoryController::class, 'export']
                    )->name('category.export');
                    Route::delete(
                        '/bulkDelete',
                        [\App\Http\Controllers\CategoryController::class, 'bulkDelete']
                    )->name('category.bulk-delete');
                    Route::post(
                        '/import',
                        [\App\Http\Controllers\CategoryController::class, 'import']
                    )->name('category.import');
                    Route::post(
                        '/{categoryId}/updateImage',
                        [\App\Http\Controllers\CategoryController::class, 'updateImage']
                    )->name('category.update-image');
                    Route::post(
                        'addImage',
                        [\App\Http\Controllers\CategoryController::class,'addImageToCategory']
                    )->name('category.add-image');

                    Route::get(
                        '{categoryId}/hasProducts',
                        [\App\Http\Controllers\CategoryController::class, 'hasProducts']
                    )
                        ->name('category.hasProducts');
                    Route::get('{categoryId}/products', [\App\Http\Controllers\CategoryController::class, 'products'])
                        ->name('category.products');
                    Route::put(
                        '{categoryId}/products',
                        [\App\Http\Controllers\CategoryController::class, 'assignProducts']
                    )
                        ->name('category.assignProducts');
                    Route::delete(
                        '{categoryId}/products',
                        [\App\Http\Controllers\CategoryController::class, 'removeProducts']
                    )
                        ->name('category.removeProducts');

                    Route::get(
                        '{categoryId}/groups',
                        [\App\Http\Controllers\CategoryGroupController::class, 'listByCategory']
                    )->name('category.groups.index');
                    Route::post(
                        '{categoryId}/groups',
                        [\App\Http\Controllers\CategoryGroupController::class, 'storeByCategory']
                    )->name('category.groups.store');
                });

                Route::put(
                    'categorygroup/{groupId}',
                    [\App\Http\Controllers\CategoryGroupController::class, 'updateGroup']
                )->name('categorygroup.update');
                Route::post(
                    'categorygroup/{groupId}',
                    [\App\Http\Controllers\CategoryGroupController::class, 'updateGroup']
                )->name('categorygroup.update.post');
                Route::delete(
                    'categorygroup/{groupId}',
                    [\App\Http\Controllers\CategoryGroupController::class, 'destroyGroup']
                )->name('categorygroup.destroy');
                Route::post(
                    'categorygroup/{groupId}/subgroups',
                    [\App\Http\Controllers\CategoryGroupController::class, 'storeSubgroup']
                )->name('categorygroup.subgroups.store');
                Route::put(
                    'categorysubgroup/{subgroupId}',
                    [\App\Http\Controllers\CategoryGroupController::class, 'updateSubgroup']
                )->name('categorysubgroup.update');
                Route::post(
                    'categorysubgroup/{subgroupId}',
                    [\App\Http\Controllers\CategoryGroupController::class, 'updateSubgroup']
                )->name('categorysubgroup.update.post');
                Route::delete(
                    'categorysubgroup/{subgroupId}',
                    [\App\Http\Controllers\CategoryGroupController::class, 'destroySubgroup']
                )->name('categorysubgroup.destroy');

                Route::group(['prefix' => 'company'], function () {
                    Route::get(
                        '/{companyId}/dashboard/filter-data',
                        [\App\Http\Controllers\Company\DashboardController::class, 'filterData']
                    )->name('company.dashboard.filterData');
                    Route::get(
                        '/{companyId}/catalogs',
                        [\App\Http\Controllers\CompanyController::class, 'catalogs']
                    )->name('company.catalogs');
                    Route::get(
                        '/{companyId}/catalogs/default',
                        [\App\Http\Controllers\CompanyController::class, 'defaultCatalog']
                    )->name('company.catalogs.default');
                    Route::get(
                        '/{companyId}/categories',
                        [\App\Http\Controllers\CompanyController::class, 'categories']
                    )->name('company.categories');
                    Route::get(
                        '/{companyId}/categories/default',
                        [\App\Http\Controllers\CompanyController::class, 'defaultCategory']
                    )->name('company.categories.default');
                    Route::get(
                        '{companyId}/category/{categoryId}',
                        [\App\Http\Controllers\CompanyController::class, 'category']
                    )->name('company.category');
                    Route::get(
                        '/{companyId}/contacts',
                        [\App\Http\Controllers\CompanyController::class, 'contacts']
                    )->name('company.contacts');
                    Route::get(
                        '/{companyId}/createdBy',
                        [\App\Http\Controllers\CompanyController::class, 'createdBy']
                    )->name('company.createdBy');
                    Route::get(
                        '/{companyId}/plan-validation',
                        [\App\Http\Controllers\CompanyController::class, 'planValidation']
                    )->name('company.planValidation');
                    Route::post(
                        '/{companyId}/prepare-order-catalog',
                        [\App\Http\Controllers\CompanyController::class, 'prepareOrderCatalog']
                    )->name('company.prepareOrderCatalog');
                    Route::post(
                        '/{companyId}/order-catalog-session',
                        [\App\Http\Controllers\CompanyController::class, 'createOrderCatalogSession']
                    )->name('company.orderCatalogSession');
                    Route::get(
                        '/{companyId}/currency',
                        [\App\Http\Controllers\CompanyController::class, 'currency']
                    )->name('company.currency');
                    Route::get(
                        '/{companyId}/currencies',
                        [\App\Http\Controllers\CompanyController::class, 'currencies']
                    )->name('company.currencies');
                    Route::get(
                        '/{companyId}/customer-groups',
                        [\App\Http\Controllers\CompanyController::class, 'customerGroups']
                    )->name('company.customer-groups');
                    Route::get(
                        '/{companyId}/customer-groups/default',
                        [\App\Http\Controllers\CompanyController::class, 'defaultCustomerGroup']
                    )->name('company.customerGroups.default');

                    Route::get(
                        '/{companyId}/currency-code',
                        [\App\Http\Controllers\CompanyController::class, 'currencyCode']
                    )->name('company.currencyCode');
                    Route::get(
                        '/{companyId}/currency-symbol',
                        [\App\Http\Controllers\CompanyController::class, 'currencySymbol']
                    )->name('company.currencySymbol');
                    Route::get(
                        '/{companyId}/formats',
                        [\App\Http\Controllers\FormatController::class, 'byCompany']
                    )->name('company.formats');
                    Route::get(
                        '/{companyId}/curconfig',
                        [\App\Http\Controllers\CompanyController::class, 'curconfig']
                    )->name('company.curconfig');
                    Route::get(
                        '/{companyId}/customers',
                        [\App\Http\Controllers\CompanyController::class, 'customers']
                    )->name('company.customers');
                    Route::get(
                        '/{companyId}/customers/count',
                        [\App\Http\Controllers\CompanyController::class, 'customersCount']
                    )->name('company.customers.count');
                    Route::get(
                        '/{companyId}/plan-limits',
                        [\App\Http\Controllers\CompanyController::class, 'planLimits']
                    )->name('company.plan-limits');
                    Route::post(
                        '/{companyId}/sync-plan-limits',
                        [\App\Http\Controllers\CompanyController::class, 'syncPlanLimits']
                    )->name('company.sync-plan-limits');
                    Route::post(
                        '/{companyId}/init-defaults',
                        [\App\Http\Controllers\CompanyController::class, 'initDefaults']
                    )->name('company.init-defaults');

                    Route::get(
                        '/{companyId}/isAssignedToUser/{rolId}',
                        [\App\Http\Controllers\CompanyController::class, 'isAssignedToUser']
                    )->name('company.isAssignedToUser');

                    Route::get(
                        '/{companyId}/orders',
                        [\App\Http\Controllers\CompanyController::class, 'orders']
                    )->name('company.orders');
                    // Rutas más específicas antes de /products (evita ambigüedad).
                    Route::get(
                        '/{companyId}/products/locations',
                        [\App\Http\Controllers\ProductController::class, 'getLocations']
                    )->name('company.products.locations');
                    Route::get(
                        '/{companyId}/products/count',
                        [\App\Http\Controllers\CompanyController::class, 'productsCount']
                    )->name('company.products.count');
                    Route::get(
                        '/{companyId}/products',
                        [\App\Http\Controllers\CompanyController::class, 'products']
                    )->name('company.products');
                    Route::post(
                        '/{companyId}/products/inventory-list',
                        [\App\Http\Controllers\ProductController::class, 'companyInventoryList']
                    )->name('company.products.inventoryList');
                    Route::post(
                        '/{companyId}/products/inventory-export',
                        [\App\Http\Controllers\ProductController::class, 'companyInventoryExport']
                    )->name('company.products.inventoryExport');
                    Route::get(
                        '/{companyId}/products/{proId}/inventory-movements',
                        [\App\Http\Controllers\ProductController::class, 'companyInventoryMovements']
                    )->name('company.products.inventoryMovements');

                    Route::get(
                        '/{companyId}/sellers',
                        [\App\Http\Controllers\CompanyController::class, 'sellers']
                    )->name('company.sellers');
                    Route::get(
                        '/{companyId}/terms-of-sale',
                        [\App\Http\Controllers\CompanyController::class, 'termsOfSale']
                    )->name('company.terms-of-sale');


                    Route::get(
                        '/{companyId}/units',
                        [\App\Http\Controllers\CompanyController::class, 'units']
                    )->name('company.units');
                    Route::get(
                        '/{companyId}/units/default',
                        [\App\Http\Controllers\CompanyController::class, 'defaultUnit']
                    )->name('company.defaultUnit');
                    Route::post(
                        '/{companyId}/units/default',
                        [\App\Http\Controllers\CompanyController::class, 'updateDefaultUnit']
                    )->name('company.updateDefaultUnit');
                    Route::get(
                        '/{companyId}/users',
                        [\App\Http\Controllers\CompanyController::class, 'users']
                    )->name('company.users');
                    Route::get(
                        '/{companyId}/warehouse-users',
                        [\App\Http\Controllers\CompanyController::class, 'warehouseUsers']
                    )->name('company.warehouseUsers');
                    Route::get(
                        '/{companyId}/warehouse-users-actives',
                        [\App\Http\Controllers\CompanyController::class, 'warehouseUsersActives']
                    )->name('company.warehouseUsersActives');
                    Route::get(
                        '/{companyId}/warehouse-users-for-order-assignment',
                        [\App\Http\Controllers\CompanyController::class, 'warehouseUsersForOrderAssignment']
                    )->name('company.warehouseUsersForOrderAssignment');

                    Route::get(
                        '/slug-{slug}',
                        [\App\Http\Controllers\CompanyController::class, 'getBySlug']
                    )->name('company.getBySlug');

                    Route::get(
                        '/name/{name}/exists',
                        [\App\Http\Controllers\CompanyController::class, 'nameExists']
                    )->name('company.nameExists');
                    Route::get(
                        '/pending-stripe-customers-count',
                        [\App\Http\Controllers\CompanyController::class, 'pendingStripeCustomersCount']
                    )->name('company.pendingStripeCustomersCount');
                    Route::get(
                        '/{companyId}/deletion-block-reason',
                        [\App\Http\Controllers\CompanyController::class, 'deletionBlockReason']
                    )->whereNumber('companyId')->name('company.deletionBlockReason');
                    Route::get(
                        '/email/{email}/exists',
                        [\App\Http\Controllers\CompanyController::class, 'emailExists']
                    )->name('company.emailExists');
                    Route::get(
                        '/phone/{phone}/exists',
                        [\App\Http\Controllers\CompanyController::class, 'phoneExists']
                    )->name('company.phoneExists');
                    Route::get(
                        '{company}/format-numeric',
                        [\App\Http\Controllers\CompanyController::class, 'formatNumeric']
                    )->name('company.formatNumeric');
                });

                Route::group(['prefix' => 'customer'], function () {
                    Route::get(
                        '/all',
                        [\App\Http\Controllers\CustomerController::class, 'all']
                    )->name('customer.all');
                    Route::post(
                        '/assignUser/{customer}',
                        [\App\Http\Controllers\CustomerController::class, 'assignUser']
                    )->name('customer.assign-user');
                    Route::post(
                        '/bulkAssignSeller',
                        [\App\Http\Controllers\CustomerController::class, 'bulkAssignSeller']
                    )->name('customer.bulk-assign-seller');
                    Route::delete(
                        '/bulkDelete',
                        [\App\Http\Controllers\CustomerController::class, 'bulkDelete']
                    )->name('customer.bulk-delete');
                    Route::post(
                        '/bulkSendCatalog',
                        [\App\Http\Controllers\CustomerController::class, 'bulkSendCatalog']
                    )->name('customer.bulk-send-catalog');
                    Route::post(
                        '/bulkUnassignSeller',
                        [\App\Http\Controllers\CustomerController::class, 'bulkUnassignSeller']
                    )->name('customer.bulk-unassign-seller');
                    Route::post(
                        '/export',
                        [\App\Http\Controllers\CustomerController::class, 'CSVExport']
                    )->name('customer.export');
                    Route::put(
                        '/{customer}/enable',
                        [\App\Http\Controllers\CustomerController::class, 'enable']
                    )->name('customer.enable');
                    Route::put(
                        '/{customer}/enableEmail',
                        [\App\Http\Controllers\CustomerController::class, 'enableEmail']
                    )->name('customer.enableEmail');
                    Route::put(
                        '/{customer}/disable',
                        [\App\Http\Controllers\CustomerController::class, 'disable']
                    )->name('customer.disable');
                    Route::put(
                        '/{customer}/disableEmail',
                        [\App\Http\Controllers\CustomerController::class, 'disableEmail']
                    )->name('customer.disableEmail');
                    Route::get(
                        '/{customer}/orders',
                        [\App\Http\Controllers\CustomerController::class, 'orders']
                    )->name('customer.orders');
                    Route::post(
                        '/{customer}/ensure-catalog-cart',
                        [\App\Http\Controllers\CustomerController::class, 'ensureCatalogCart']
                    )->name('customer.ensureCatalogCart');
                    Route::post(
                        '/{customer}/ensure-portal-session-cart',
                        [\App\Http\Controllers\CustomerController::class, 'ensurePortalSessionCart']
                    )->name('customer.ensurePortalSessionCart');
                    Route::get(
                        '/{customerId}/has-orders',
                        [\App\Http\Controllers\CustomerController::class, 'hasOrders']
                    )->name('order.customerHasOrders');
                    Route::get(
                        '/{customerId}/seller',
                        [\App\Http\Controllers\CustomerController::class, 'seller']
                    )->name('customer.seller');
                    Route::get(
                        '/{customerId}/product/{productId}/price',
                        [\App\Http\Controllers\CustomerController::class, 'productPrice']
                    )
                        ->name('customer.productPrice');

                    Route::post(
                        '/email/exists',
                        [\App\Http\Controllers\CustomerController::class, 'emailExists']
                    )->name('customer.emailExists');
                    Route::post(
                        '/update-email',
                        [\App\Http\Controllers\CustomerController::class, 'updateEmail']
                    )->name('customer.updateEmail');
                    Route::get(
                        '/{customer}/emails',
                        [\App\Http\Controllers\CustomerEmailController::class, 'listForCustomer']
                    )->name('customer.emails.index');
                    Route::post(
                        '/{customer}/emails',
                        [\App\Http\Controllers\CustomerEmailController::class, 'storeForCustomer']
                    )->name('customer.emails.store');
                    Route::put(
                        '/{customer}/emails/{customerEmail}',
                        [\App\Http\Controllers\CustomerEmailController::class, 'updateForCustomer']
                    )->name('customer.emails.update');
                    Route::delete(
                        '/{customer}/emails/{customerEmail}',
                        [\App\Http\Controllers\CustomerEmailController::class, 'destroyForCustomer']
                    )->name('customer.emails.destroy');
                    Route::post(
                        '/{customer}/emails/{customerEmail}/set-primary',
                        [\App\Http\Controllers\CustomerEmailController::class, 'setPrimary']
                    )->name('customer.emails.setPrimary');
                    Route::put(
                        '/updateGroup',
                        [\App\Http\Controllers\CustomerController::class, 'updateGroup']
                    )->name('customer.updateGroup');
                    Route::get(
                        '/company/{companyId}/bussines-name/{bussinesName}/exists',
                        [\App\Http\Controllers\CustomerController::class, 'bussinesNameExists']
                    )->name('customer.bussinesNameExists');
                    Route::get(
                        '/company/{companyId}/find-by-business-name',
                        [\App\Http\Controllers\CustomerController::class, 'findByBusinessName']
                    )->name('customer.findByBusinessName');
                    Route::get(
                        '/company/{companyId}/find-by-erp',
                        [\App\Http\Controllers\CustomerController::class, 'findByErpId']
                    )->name('customer.findByErpId');
                    Route::get(
                        '/company/{companyId}/by-business-name/{businessName}',
                        [\App\Http\Controllers\CustomerController::class, 'getByBusinessName']
                    )->name('customer.getByBusinessName');
                });

                Route::group(['prefix' => 'customergroup'], function () {
                    Route::get(
                        '/all',
                        [\App\Http\Controllers\CustomerGroupController::class, 'all']
                    )->name('customergroup.all');
                    Route::put(
                        '/{groupId}/enable',
                        [\App\Http\Controllers\CustomerGroupController::class, 'enable']
                    )->name('customergroup.enable');
                    Route::put(
                        '/{groupId}/disable',
                        [\App\Http\Controllers\CustomerGroupController::class, 'disable']
                    )->name('customergroup.disable');
                    Route::put(
                        '/{groupId}/setDefault',
                        [\App\Http\Controllers\CustomerGroupController::class, 'setDefault']
                    )->name('customergroup.setDefault');
                    Route::get(
                        '/name-{name}/company/{companyId}/exists',
                        [\App\Http\Controllers\CustomerGroupController::class, 'exists']
                    )->name('customergroup.exists');
                    Route::get(
                        '/name-{name}/company/{companyId}',
                        [\App\Http\Controllers\CustomerGroupController::class, 'showByName']
                    )->name('customergroup.showByName');
                    Route::get(
                        '/{customerGroupId}/customers',
                        [\App\Http\Controllers\CustomerGroupController::class, 'customers']
                    )->name('customergroup.customers');
                });

                Route::group(['prefix' => 'detailcar'], function () {
                    Route::delete(
                        '/{token}/product/{productId}',
                        [\App\Http\Controllers\DetailCarController::class, 'removeProduct']
                    )->name('detailcar.removeProduct');
                    Route::delete(
                        '/session/{sessionId}',
                        [\App\Http\Controllers\DetailCarController::class, 'destroyBySession']
                    )->name('detailcar.destroyBySession');
                    Route::get(
                        '/{sessionId}/products',
                        [\App\Http\Controllers\DetailCarController::class, 'orderedProducts']
                    )->name('detailcar.orderedProducts');
                    Route::get(
                        '/{token}/product/{productId}',
                        [\App\Http\Controllers\DetailCarController::class, 'product']
                    )->name('detailcar.product');
                });

                Route::get(
                    '/language/actives',
                    [\App\Http\Controllers\LanguageController::class, 'actives']
                )->name('language.actives');

                Route::group(['prefix' => 'order'], function () {
                    Route::post(
                        '/sanitize-document',
                        [\App\Http\Controllers\OrderController::class, 'sanitizeDocumentOrders']
                    )->name('order.sanitizeDocument');

                    Route::post(
                        '/status-by-order-number',
                        [\App\Http\Controllers\OrderController::class, 'updateStatusByOrderNumber']
                    )->name('order.updateStatusByOrderNumber');

                    Route::post(
                        '/update-delivered-item',
                        [\App\Http\Controllers\OrderController::class, 'updateDeliveredItem']
                    )->name('order.updateDeliveredItem');

                    Route::post(
                        '/set-status',
                        [\App\Http\Controllers\Warehouse\WarehouseOrdersController::class, 'setStatus']
                    )->name('order.setStatus');

                    Route::post(
                        '/inventory/orders-list',
                        [\App\Http\Controllers\OrderController::class, 'companyInventoryOrdersListPost']
                    )->name('order.inventory.ordersList');

                    Route::post(
                        '/inventory/categories-for-select',
                        [\App\Http\Controllers\OrderController::class, 'companyInventoryCategoriesForSelectPost']
                    )->name('order.inventory.categoriesForSelect');

                    Route::post(
                        '/inventory/products-for-add',
                        [\App\Http\Controllers\OrderController::class, 'companyInventoryProductsForAddPost']
                    )->name('order.inventory.productsForAdd');

                    Route::post(
                        '/inventory/create',
                        [\App\Http\Controllers\OrderController::class, 'companyInventoryCreatePost']
                    )->name('order.inventory.create');

                    Route::post(
                        '/inventory/add-one-product',
                        [\App\Http\Controllers\OrderController::class, 'companyInventoryAddOneProductPost']
                    )->name('order.inventory.addOneProduct');

                    Route::post(
                        '/inventory/export-rows',
                        [\App\Http\Controllers\OrderController::class, 'companyInventoryExportRowsPost']
                    )->name('order.inventory.exportRows');

                    Route::get(
                        '/inventory/order/{orderId}',
                        [\App\Http\Controllers\OrderController::class, 'companyInventoryShow']
                    )->name('order.inventory.show');

                    Route::get(
                        '/inventory/order/{orderId}/details',
                        [\App\Http\Controllers\OrderController::class, 'companyInventoryDetails']
                    )->name('order.inventory.details');

                    Route::delete(
                        '/inventory/detail/{detailId}',
                        [\App\Http\Controllers\OrderController::class, 'companyInventoryDeleteDetail']
                    )->name('order.inventory.deleteDetail');

                    Route::get(
                        '/warehouse/status-list',
                        [\App\Http\Controllers\OrderController::class, 'companyWarehouseMvcStatusList']
                    )->name('order.warehouse.mvc.statusList');

                    Route::get(
                        '/warehouse/order/{orderId}',
                        [\App\Http\Controllers\OrderController::class, 'companyWarehouseMvcShow']
                    )->name('order.warehouse.mvc.show');

                    Route::get(
                        '/warehouse/order/{orderId}/details',
                        [\App\Http\Controllers\OrderController::class, 'companyWarehouseMvcDetails']
                    )->name('order.warehouse.mvc.details');

                    Route::get(
                        '/warehouse/previously-ordered',
                        [\App\Http\Controllers\OrderController::class, 'companyWarehouseMvcPreviouslyOrdered']
                    )->name('order.warehouse.mvc.previouslyOrdered');

                    Route::get(
                        '/warehouse/company/{companyId}/products-active',
                        [\App\Http\Controllers\OrderController::class, 'companyWarehouseMvcProductsActive']
                    )->name('order.warehouse.mvc.productsActive');

                    Route::get(
                        '/payed/count',
                        [\App\Http\Controllers\OrderController::class, 'allPayedCount']
                    )->name('order.payedCount');

                    Route::get(
                        '/{orderId}/details',
                        [\App\Http\Controllers\OrderController::class, 'details']
                    )->name('order.details');

                    Route::get(
                        '/customer/{customerId}/payed',
                        [\App\Http\Controllers\OrderController::class, 'payedOfCustomer']
                    )->name('order.companyCustomerPayedTotalOrders');

                    Route::get(
                        '/customer/{customerId}/latest',
                        [\App\Http\Controllers\OrderController::class, 'listLatest']
                    )->name('order.latest');
                    Route::get(
                        '/company/{companyId}/inventory',
                        [\App\Http\Controllers\OrderController::class, 'companyInventoryList']
                    )->name('order.company.inventoryList');
                    Route::post(
                        '/warehouse-user/orders/warehouse-assigned',
                        [\App\Http\Controllers\OrderController::class, 'listOrdersByWarehouseUserWarehouseAssigned']
                    )->name('order.warehouseUser.orders.warehouseAssigned');
                    Route::post(
                        '/warehouse-user/orders',
                        [\App\Http\Controllers\OrderController::class, 'listOrdersByWarehouseUser']
                    )->name('order.warehouseUser.orders');
                    Route::get(
                        '/company/{companyId}/warehouse',
                        [\App\Http\Controllers\OrderController::class, 'warehouse']
                    )->name('order.company.warehouse');
                    Route::get(
                        '/company/{companyId}/getOrderNumber/orderType/{type}',
                        [\App\Http\Controllers\OrderController::class, 'generateOrderNumber']
                    )->name('order.company.generateOrderNumber');
                    Route::get(
                        '{orderId}/product/{productId}',
                        [\App\Http\Controllers\OrderController::class, 'getProductDetail']
                    )->name('order.getProductDetail');
                    Route::get(
                        '/company/{companyId}/customer/{customerId}/previouslyOrderedProducts',
                        [\App\Http\Controllers\OrderController::class, 'previouslyOrderedProducts']
                    )->name('order.previouslyOrderedProducts');

                    Route::post(
                        '/invitation',
                        [\App\Http\Controllers\Company\CatalogRequestController::class, 'createInvitationToken']
                    )->name('order.getInvitationToken');
                    Route::get(
                        '/invitation/{token}',
                        [\App\Http\Controllers\Company\CatalogCartController::class, 'createCartFromToken']
                    )->name('order.decryptInvitationToken');

                    Route::get(
                        '/{orderId}/statushistory',
                        [\App\Http\Controllers\OrderStatusHistoryController::class, 'historyOrderStatus']
                    )->name('orderstatushistory.historyOrderStatus');

                    Route::get(
                        '/{orderId}/total-details',
                        [\App\Http\Controllers\OrderController::class, 'totalDetails']
                    )->name('order.totalDetails');

                    Route::patch(
                        '/{order}/status',
                        [\App\Http\Controllers\OrderController::class, 'updateStatus']
                    )->name('order.updateStatus');
                });

                Route::group(['prefix' => 'product'], function () {
                    Route::get(
                        '/all',
                        [\App\Http\Controllers\ProductController::class, 'all']
                    )->name('product.all');
                    Route::get(
                        '/skus',
                        [\App\Http\Controllers\ProductController::class, 'skus']
                    )->name('product.skus');
                    Route::get(
                        '/sku-{sku}',
                        [\App\Http\Controllers\ProductController::class, 'getBySku']
                    )->name('product.bySku');
                    Route::put('{product}/enable', [\App\Http\Controllers\ProductController::class, 'enable'])
                        ->name('product.enable');
                    Route::put('{product}/disable', [\App\Http\Controllers\ProductController::class, 'disable']
                    )->name('product.disable');
                    Route::put('status', [\App\Http\Controllers\ProductController::class, 'bulkStatusChange']
                    )->name('product.bulkStatusChange');;
                    Route::delete(
                        '/bulkDelete',
                        [\App\Http\Controllers\ProductController::class, 'bulkDelete']
                    )->name('product.bulk-delete');
                    Route::get(
                        '/{productId}/categories',
                        [\App\Http\Controllers\ProductController::class, 'categories']
                    )->name('product.categories');
                    Route::get(
                        '/price-list',
                        [\App\Http\Controllers\ProductController::class, 'getPriceList']
                    )->name('product.priceList');
                    Route::get('export', [\App\Http\Controllers\ProductController::class, 'export'])->name('product.export');
                    Route::post('export', [\App\Http\Controllers\ProductController::class, 'export'])->name('product.export.post');
                    Route::post(
                        'assign-ai-images',
                        [\App\Http\Controllers\ProductController::class, 'assignAiImages']
                    )->name('product.assignAiImages');
                    Route::get(
                        '{productId}/specifications',
                        [\App\Http\Controllers\ProductController::class, 'getSpecifications']
                    )->name('product.getSpecifications');
                    Route::put(
                        '{productId}/specifications',
                        [\App\Http\Controllers\ProductController::class, 'updateSpecifications']
                    )->name('product.updateSpecifications');
                    Route::get(
                        '{productId}/images',
                        [\App\Http\Controllers\ProductImageController::class, 'getByProduct']
                    )->name('product.getImagesByProduct');
                    Route::post(
                        '{productId}/image/{imageUUID}',
                        [\App\Http\Controllers\ProductImageController::class,'addImageToProduct']
                    )->name('product.addImageToProduct');
                    Route::put(
                        '{productId}/images/resort',
                        [\App\Http\Controllers\ProductImageController::class,'sortImagesOfProduct']
                    )->name('product.resortImagesOfProduct');;
                    Route::delete(
                        '{productId}/image/{imageUUID}',
                        [\App\Http\Controllers\ProductImageController::class,'removeImageFromProduct']
                    )->name('product.removeImageFromProduct');

                });

                Route::group(['prefix' => 'productimage'], function () {

                    Route::get(
                        '/product/{productId}/first',
                        [\App\Http\Controllers\ProductImageController::class, 'first']
                    )->name('productimage.first');
                });

                /* TODO: translate status names on lang */
                Route::get(
                    '/statuscart/getByLangColumn/{langCol}',
                    [\App\Http\Controllers\StatusCartController::class, 'getByLangColumn']
                )->name('statuscart.getByLangColumn');

                Route::group(['prefix' => 'statusrol'], function () {
                    Route::get(
                        '/rol/{rolId}',
                        [\App\Http\Controllers\StatusRolController::class, 'listByRole']
                    )->name('statusrol.listByRole');
                });

                Route::group(['prefix' => 'termsofsale'], function () {
                    Route::get(
                        '/all',
                        [\App\Http\Controllers\TermsOfSaleController::class, 'all']
                    )->name('termsofsale.all');
                    Route::get(
                        '/company/{companyId}',
                        [\App\Http\Controllers\TermsOfSaleController::class, 'default']
                    )->name('termsofsale.default');
                    Route::get(
                        '/company/{companyId}/{termsName}/exists',
                        [\App\Http\Controllers\TermsOfSaleController::class, 'exists']
                    )->name('termsofsale.exists');
                    Route::put(
                        '/company/{companyId}',
                        [\App\Http\Controllers\TermsOfSaleController::class, 'updateDefault']
                    )->name('termsofsale.updateDefault');
                    Route::get(
                        '/company/{companyId}/name-{name}',
                        [\App\Http\Controllers\TermsOfSaleController::class, 'showByName']
                    )->name('termsofsale.showByName');
                    Route::put(
                        '/{termId}/enable',
                        [\App\Http\Controllers\TermsOfSaleController::class, 'enable']
                    )->name('termsofsale.enable');
                    Route::put(
                        '/{termId}/disable',
                        [\App\Http\Controllers\TermsOfSaleController::class, 'disable']
                    )->name('termsofsale.disable');
                    Route::put(
                        '/{termId}/setDefault',
                        [\App\Http\Controllers\TermsOfSaleController::class, 'setDefault']
                    )->name('termsofsale.setDefault');
                });

                Route::group(['prefix' => 'unit'], function () {
                    Route::get(
                        '/company/{companyId}',
                        [\App\Http\Controllers\UnitController::class, 'default']
                    )->name('unit.default');
                    Route::get(
                        '/{companyId}/{unitName}/exists',
                        [\App\Http\Controllers\UnitController::class, 'exists']
                    )->name('unit.exists');
                    Route::put(
                        '/{unitId}/enable',
                        [\App\Http\Controllers\UnitController::class, 'enable']
                    )->name('unit.enable');
                    Route::put(
                        '/{unitId}/disable',
                        [\App\Http\Controllers\UnitController::class, 'disable']
                    )->name('unit.disable');
                    Route::put(
                        '/{unitId}/setDefault',
                        [\App\Http\Controllers\UnitController::class, 'setDefault']
                    )->name('unit.setDefault');
                });

                Route::group(['prefix' => 'user'], function () {
                    Route::get(
                        '/{userId}/company/{companyId}/customer',
                        [\App\Http\Controllers\UserController::class, 'customer']
                    )->name('user.customer');
                    Route::get(
                        '/{userId}/company/{companyId}/seller',
                        [\App\Http\Controllers\UserController::class, 'seller']
                    )->name('user.seller');
                    Route::put(
                        '/{userId}/enable',
                        [\App\Http\Controllers\UserController::class, 'enable']
                    )->name('user.enable');
                    Route::put(
                        '/{userId}/disable',
                        [\App\Http\Controllers\UserController::class, 'disable']
                    )->name('user.disable');
                });

                Route::group(['prefix' => 'sellerprofiles'], function () {
                    Route::get(
                        '/actives',
                        [\App\Http\Controllers\SellerProfilesController::class, 'actives']
                    )->name('sellerprofiles.actives');
                });

                Route::group(['prefix' => 'sellers'], function () {
                    Route::get(
                        '/',
                        [\App\Http\Controllers\VendorController::class, 'listSellersByCompany']
                    )->name('sellers.list-by-company');
                });

                Route::group(['prefix' => 'formatnumeric'], function () {
                    Route::get(
                        '/default',
                        [\App\Http\Controllers\FormatNumericController::class, 'default']
                    )->name('formatnumeric.default');
                });
                Route::group(['prefix' => 'formatdate'], function () {
                    Route::get(
                        '/default',
                        [\App\Http\Controllers\FormatDateController::class, 'default']
                    )->name('formatdate.default');
                });

                Route::group(['prefix' => 'vendor'], function () {
                    Route::get(
                        '/{vendor}/customers',
                        [\App\Http\Controllers\VendorController::class, 'customers']
                    )->name('vendor.customers');
                    Route::put(
                        '/{seller}/enable',
                        [\App\Http\Controllers\VendorController::class, 'enable']
                    )->name('seller.enable');
                    Route::put(
                        '/{seller}/disable',
                        [\App\Http\Controllers\VendorController::class, 'disable']
                    )->name('seller.disable');
                    Route::get(
                        '/company/{companyId}/email/{email}/exists',
                        [\App\Http\Controllers\VendorController::class, 'emailExists']
                    )->name('vendor.emailExists');
                    Route::get(
                        '/email/{email}/exists',
                        [\App\Http\Controllers\VendorController::class, 'existEmailInOtherCompany']
                    )->name('vendor.existEmailInOtherCompany');
                    Route::get(
                        '/company/{companyId}/name-{vendorName}/exists',
                        [\App\Http\Controllers\VendorController::class, 'nameExists']
                    )->name('vendor.nameExists');
                    Route::get(
                        '/company/{companyId}/username-{userName}/exists',
                        [\App\Http\Controllers\VendorController::class, 'userNameExist']
                    )->name('vendor.userNameExist');
                });

                Route::group(['prefix' => 'warehouse'], function () {
                    Route::get(
                        '/company/{companyId}/for-order-assignment',
                        [\App\Http\Controllers\WarehouseController::class, 'forOrderAssignment']
                    )->name('warehouse.for-order-assignment');
                    Route::post(
                        '/assignUser/{warehouse}',
                        [\App\Http\Controllers\WarehouseController::class, 'assignUser']
                    )->name('warehouse.assign-user');
                    Route::post(
                        '/unassignUser/{warehouse}',
                        [\App\Http\Controllers\WarehouseController::class, 'unassignUser']
                    )->name('warehouse.unassign-user');

                    // Sección warehouse: órdenes y entrega
                    Route::post(
                        '/set-status',
                        [\App\Http\Controllers\Warehouse\WarehouseOrdersController::class, 'setStatus']
                    )->name('warehouse.setStatus');
                    Route::match(
                        ['get', 'post'],
                        '/credit-notes/list',
                        [\App\Http\Controllers\Warehouse\WarehouseOrdersController::class, 'listCreditNotes']
                    )->name('warehouse.creditNotes.list');
                    Route::post(
                        '/credit-notes',
                        [\App\Http\Controllers\Warehouse\WarehouseOrdersController::class, 'createCreditNote']
                    )->name('warehouse.creditNotes.create');
                    Route::post(
                        '/update-delivered-item',
                        [\App\Http\Controllers\Warehouse\WarehouseOrdersController::class, 'updateDeliveredItem']
                    )->name('warehouse.updateDeliveredItem');
                    Route::post(
                        '/update-inventory-status',
                        [\App\Http\Controllers\Warehouse\WarehouseOrdersController::class, 'updateInventoryStatus']
                    )->name('warehouse.updateInventoryStatus');
                    Route::post(
                        '/update-inventory-stock',
                        [\App\Http\Controllers\Warehouse\WarehouseOrdersController::class, 'updateInventoryStock']
                    )->name('warehouse.updateInventoryStock');
                    Route::match(
                        ['get', 'post'],
                        '/orders/pending',
                        [\App\Http\Controllers\Warehouse\WarehouseOrdersController::class, 'pendingOrders']
                    )->name('warehouse.orders.pending');
                    Route::match(
                        ['get', 'post'],
                        '/orders/completed',
                        [\App\Http\Controllers\Warehouse\WarehouseOrdersController::class, 'completedOrders']
                    )->name('warehouse.orders.completed');
                    Route::match(
                        ['get', 'post'],
                        '/orders/inventory',
                        [\App\Http\Controllers\Warehouse\WarehouseOrdersController::class, 'inventoryOrders']
                    )->name('warehouse.orders.inventory');
                    Route::match(
                        ['get', 'post'],
                        '/orders/inventory/with-products',
                        [\App\Http\Controllers\Warehouse\WarehouseOrdersController::class, 'inventoryOrdersWithProducts']
                    )->name('warehouse.orders.inventory.withProducts');
                    Route::match(
                        ['get', 'post'],
                        '/orders/assigned-and-completed',
                        [\App\Http\Controllers\Warehouse\WarehouseOrdersController::class, 'assignedAndCompletedOrders']
                    )->name('warehouse.orders.assignedAndCompleted');

                    // Listados simples solo por warehouse_id (formato order/list + delivered_*)
                    Route::match(
                        ['get', 'post'],
                        '/by-warehouse-id/assigned',
                        [\App\Http\Controllers\Warehouse\WarehouseOrdersController::class, 'listAssignedByWarehouseId']
                    )->name('warehouse.byWarehouseId.assigned');
                    Route::match(
                        ['get', 'post'],
                        '/by-warehouse-id/completed',
                        [\App\Http\Controllers\Warehouse\WarehouseOrdersController::class, 'listCompletedByWarehouseId']
                    )->name('warehouse.byWarehouseId.completed');
                    Route::match(
                        ['get', 'post'],
                        '/by-warehouse-id/inventory',
                        [\App\Http\Controllers\Warehouse\WarehouseOrdersController::class, 'listInventoryByWarehouseId']
                    )->name('warehouse.byWarehouseId.inventory');

                    // Detalle de orden (después de pending/completed/inventory para no capturar esos paths)
                    Route::get(
                        '/orders/{orderId}',
                        [\App\Http\Controllers\Warehouse\WarehouseOrdersController::class, 'showOrder']
                    )->whereNumber('orderId')->name('warehouse.orders.show');
                    Route::get(
                        '/orders/{orderId}/full',
                        [\App\Http\Controllers\Warehouse\WarehouseOrdersController::class, 'showOrderFull']
                    )->whereNumber('orderId')->name('warehouse.orders.showFull');
                    Route::get(
                        '/orders/{orderId}/items',
                        [\App\Http\Controllers\Warehouse\WarehouseOrdersController::class, 'showOrderItems']
                    )->whereNumber('orderId')->name('warehouse.orders.items');
                });

                Route::get(
                    '/warehouseuser/company/{companyId}/email/{email}/exists',
                    [\App\Http\Controllers\WarehouseUserController::class, 'emailExists']
                )->name('warehouseuser.emailExists');

                Route::apiResource('admin', \App\Http\Controllers\AdminController::class);
                Route::apiResource('appupdate', \App\Http\Controllers\AppUpdateController::class);
                Route::apiResource('banner', \App\Http\Controllers\BannerController::class);
                Route::apiResource('catalogcart', \App\Http\Controllers\Company\CatalogCartController::class);
                Route::apiResource('catalog', \App\Http\Controllers\Company\CatalogController::class);
                Route::apiResource('catalogrequest', \App\Http\Controllers\Company\CatalogRequestController::class);
                Route::apiResource('catalogsentmail', \App\Http\Controllers\Company\CatalogSentMailController::class);
                Route::apiResource('category', \App\Http\Controllers\CategoryController::class);
                Route::apiResource('changesapp', \App\Http\Controllers\ChangesAppController::class);
                Route::group(['prefix' => 'city'], function () {
                    Route::get(
                        '/all',
                        [\App\Http\Controllers\CityController::class, 'all']
                    )->name('city.all');
                });
                Route::apiResource('city', \App\Http\Controllers\CityController::class);
                Route::get(
                    'dashboard/admin-stats',
                    [\App\Http\Controllers\DashboardController::class, 'adminStats']
                )->name('dashboard.adminStats');
                Route::get(
                    'stripe-config',
                    [\App\Http\Controllers\StripeConfigController::class, 'show']
                )->name('stripe-config.show');
                Route::put(
                    'stripe-config',
                    [\App\Http\Controllers\StripeConfigController::class, 'update']
                )->name('stripe-config.update');
                Route::apiResource('cm', \App\Http\Controllers\CmController::class, ['except' => ['show']]);
                Route::get(
                    '/companiescategory/actives',
                    [\App\Http\Controllers\CompaniesCategoryController::class, 'actives']
                )->name('companiescategory.actives');
                Route::apiResource('companiescategory', \App\Http\Controllers\CompaniesCategoryController::class);
                Route::apiResource('companycontact', \App\Http\Controllers\CompanyContactController::class);
                Route::apiResource('initialordernumber', \App\Http\Controllers\InitialOrderNumberController::class);
                Route::apiResource('company', \App\Http\Controllers\CompanyController::class);
                Route::apiResource('companystatusorder', \App\Http\Controllers\CompanyStatusOrderController::class);
                Route::apiResource('consoleconfig', \App\Http\Controllers\ConsoleconfigController::class);
                Route::apiResource('consoleuser', \App\Http\Controllers\ConsoleuserController::class);
                
                Route::get(
                    '/places',
                    [\App\Http\Controllers\PlaceController::class, 'index']
                )->name('places.index');
                Route::group(['prefix' => 'country'], function () {
                    Route::get(
                        '/all',
                        [\App\Http\Controllers\CountryController::class, 'all']
                    )->name('country.all');
                });
                Route::apiResource('country', \App\Http\Controllers\CountryController::class);

                Route::apiResource('currencyformat', \App\Http\Controllers\CurrencyFormatController::class);
                Route::group(['prefix' => 'currency'], function () {
                    Route::get(
                        '/grouped-by-continent',
                        [\App\Http\Controllers\CurrencyController::class, 'groupedByContinent']
                    )->name('currency.groupedByContinent');
                });
                Route::get(
                    'currencybycountries/grouped',
                    [\App\Http\Controllers\CurrencybycountryController::class, 'grouped']
                )->name('currencybycountries.grouped');
                Route::apiResource('currency', \App\Http\Controllers\CurrencyController::class);
                Route::apiResource('customergroup', \App\Http\Controllers\CustomerGroupController::class);
                Route::apiResource('customer', \App\Http\Controllers\CustomerController::class);
                Route::apiResource('deliveredorder', \App\Http\Controllers\DeliveredOrderController::class);
                Route::apiResource('detailcar', \App\Http\Controllers\DetailCarController::class);
                Route::apiResource('deviceinfo', \App\Http\Controllers\DeviceinfoController::class);
                Route::apiResource('dictionarygp', \App\Http\Controllers\DictionaryGpController::class);
                Route::apiResource('enquiry', \App\Http\Controllers\EnquiryController::class);
                Route::apiResource('formatdate', \App\Http\Controllers\FormatDateController::class);
                Route::apiResource('formatnumeric', \App\Http\Controllers\FormatNumericController::class);
                Route::apiResource('format', \App\Http\Controllers\FormatController::class)
                    ->only(['show', 'store', 'update', 'destroy']);
                Route::apiResource('gpslog', \App\Http\Controllers\GpslogController::class);
                Route::apiResource('gpstracker', \App\Http\Controllers\GpstrackerController::class);
                Route::apiResource('importhistory', \App\Http\Controllers\ImportHistoryController::class);

                Route::post('plan/change-status', [\App\Http\Controllers\PlanController::class, 'changeStatus'])
                    ->name('plan.changeStatus');
                Route::apiResource('plan', \App\Http\Controllers\PlanController::class);
                Route::get(
                    'subscription/{subscription}/has-blocking-invoice',
                    [\App\Http\Controllers\SubscriptionController::class, 'hasBlockingInvoice']
                )->whereNumber('subscription')->name('subscription.hasBlockingInvoice');
                Route::apiResource('subscription', \App\Http\Controllers\SubscriptionController::class);
                Route::apiResource('invoice', \App\Http\Controllers\InvoiceController::class);

                // GET binario / no-image / logo: rutas públicas arriba.
                // except(show): apiResource::show pisa images/{uuid} y exige auth:sanctum
                // (el proxy MVC y <img src> fallan con 302→login / 404).
                Route::apiResource('images', \App\Http\Controllers\ImageController::class)
                    ->except(['show']);
                Route::group(['prefix' => 'inventory'], function () {
                    Route::get(
                        'company/{companyId}/product/{productId}',
                        [\App\Http\Controllers\InventoryController::class, 'show']
                    );
                    Route::put(
                        'company/{companyId}/product/{productId}',
                        [\App\Http\Controllers\InventoryController::class, 'update']
                    );
                    Route::delete(
                        'company/{companyId}/product/{productId}',
                        [\App\Http\Controllers\InventoryController::class, 'destroy']
                    );
                });
                Route::apiResource(
                    'inventory',
                    \App\Http\Controllers\InventoryController::class,
                    ['except' => ['show', 'update', 'destroy']]
                );
                Route::apiResource('key', \App\Http\Controllers\KeyController::class);
                Route::apiResource('language', \App\Http\Controllers\LanguageController::class);
                Route::apiResource('log', \App\Http\Controllers\LogController::class);
                Route::apiResource('mailslog', \App\Http\Controllers\MailsLogController::class);
                Route::apiResource('navbar', \App\Http\Controllers\NavbarController::class);
                Route::apiResource('navbarrolid', \App\Http\Controllers\NavbarRolIdController::class);
                Route::apiResource('newsletter', \App\Http\Controllers\NewsletterController::class);
                Route::apiResource('orderdetail', \App\Http\Controllers\OrderDetailController::class);
                Route::post(
                    'order/create',
                    [\App\Http\Controllers\OrderController::class, 'store']
                )->name('order.create');
                Route::get(
                    'order/list',
                    [\App\Http\Controllers\OrderController::class, 'list']
                )->name('order.list');
                Route::post(
                    'order/assign-warehouse',
                    [\App\Http\Controllers\OrderController::class, 'assignWarehouseSimple']
                )->name('order.assign-warehouse-simple');
                Route::post(
                    'order/sendOrder',
                    [\App\Http\Controllers\OrderController::class, 'sendOrder']
                )->name('order.sendOrder');
                Route::get(
                    'order/{order}/full',
                    [\App\Http\Controllers\OrderController::class, 'showFull']
                )->name('order.full');
                Route::post(
                    'order/{order}/generatePdf',
                    [\App\Http\Controllers\OrderController::class, 'generatePdf']
                )->name('order.generatePdf');
                Route::apiResource('order', \App\Http\Controllers\OrderController::class);
                Route::apiResource('ordercategory', \App\Http\Controllers\OrderCategoryController::class);
                Route::apiResource('orderstatushistory', \App\Http\Controllers\OrderStatusHistoryController::class);
                Route::apiResource('productimage', \App\Http\Controllers\ProductImageController::class);

                Route::apiResource('product', \App\Http\Controllers\ProductController::class);
                Route::apiResource('managerprofiles', \App\Http\Controllers\ManagerProfilesController::class);
                Route::apiResource('rols', \App\Http\Controllers\RolsController::class);
                Route::apiResource('setting', \App\Http\Controllers\SettingController::class);
                Route::group(['prefix' => 'state'], function () {
                    Route::get(
                        '/all',
                        [\App\Http\Controllers\StateController::class, 'all']
                    )->name('state.all');
                });
                Route::apiResource('state', \App\Http\Controllers\StateController::class);
                Route::get(
                    'postal-codes/search',
                    [\App\Http\Controllers\PostalCodeController::class, 'search']
                )->name('postal-codes.search');
                Route::apiResource('statuscart', \App\Http\Controllers\StatusCartController::class);
                Route::get(
                    'statusorder/list',
                    [\App\Http\Controllers\StatusOrderController::class, 'listAll']
                )->name('statusorder.list');
                Route::apiResource('statusorder', \App\Http\Controllers\StatusOrderController::class);
                Route::apiResource('statusrol', \App\Http\Controllers\StatusRolController::class);
                Route::apiResource('termsofsale', \App\Http\Controllers\TermsOfSaleController::class);
                Route::apiResource('unit', \App\Http\Controllers\UnitController::class);
                Route::apiResource('user', \App\Http\Controllers\UserController::class);
                Route::apiResource('sellerprofiles', \App\Http\Controllers\SellerProfilesController::class);
                Route::apiResource('vendor', \App\Http\Controllers\VendorController::class);
                Route::apiResource('warehouse', \App\Http\Controllers\WarehouseController::class);
                Route::apiResource('warehouseuser', \App\Http\Controllers\WarehouseUserController::class);
                Route::apiResource('worderslist', \App\Http\Controllers\WordersListController::class);

                Route::apiResource('orderflows', \App\Http\Controllers\OrderFlowController::class);
                Route::apiResource('orderflowtransitions', \App\Http\Controllers\OrderFlowTransitionController::class);
            }
        );
    }
);
