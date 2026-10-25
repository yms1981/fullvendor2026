<?php

namespace App\Providers;

use App\Console\Commands\SyncSchemaInsteadOfMigrateCommand;
use App\Models\Category;
use App\Models\Company;
use App\Models\Customer;
use App\Models\Order;
use App\Models\Product;
use App\Models\Subscription;
use App\Observers\CompanyStripeCustomerObserver;
use App\Observers\CustomerCustIdKorObserver;
use App\Observers\CustomerOrderProductFifoObserver;
use App\Observers\MobileChangeLogObserver;
use App\Observers\SubscriptionCompanyLimitsObserver;
use App\Observers\SubscriptionInvoiceObserver;
use Illuminate\Database\Console\Migrations\MigrateCommand;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Illuminate\Cache\RateLimiting\Limit;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(MigrateCommand::class, SyncSchemaInsteadOfMigrateCommand::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Company::observe(CompanyStripeCustomerObserver::class);
        Subscription::observe(SubscriptionInvoiceObserver::class);
        Subscription::observe(SubscriptionCompanyLimitsObserver::class);
        Customer::observe(CustomerCustIdKorObserver::class);

        $mobileChangeObserver = MobileChangeLogObserver::class;
        Product::observe($mobileChangeObserver);
        Customer::observe($mobileChangeObserver);
        Category::observe($mobileChangeObserver);
        Order::observe($mobileChangeObserver);
        Order::observe(CustomerOrderProductFifoObserver::class);

        \Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::except([
            'api/v2/login'
        ]);
        
        // Configurar rate limiting para las rutas API: aumentar límite significativamente o deshabilitar
        // Opción 1: Deshabilitar rate limiting completamente (recomendado para desarrollo/producción con muchas peticiones)
        RateLimiter::for('api', function (Request $request) {
            return Limit::none();
        });
        
        // Opción 2: Si prefieres mantener throttle pero con límite muy alto, comenta la línea anterior
        // y descomenta esta (50000 requests por minuto):
         //RateLimiter::for('api', function (Request $request) {
           //  return Limit::perMinute(500000)->by($request->user()?->id ?: $request->ip());
         //});
        
        // Configurar TrustProxies para Docker: confiar en todos los proxies
        // Esto asegura que Laravel use el host correcto de la petición
        Request::setTrustedProxies(['*'], Request::HEADER_X_FORWARDED_FOR | Request::HEADER_X_FORWARDED_HOST | Request::HEADER_X_FORWARDED_PORT | Request::HEADER_X_FORWARDED_PROTO);
    }
}
