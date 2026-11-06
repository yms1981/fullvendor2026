<?php

namespace App\Providers;

use App\Console\Commands\SyncSchemaInsteadOfMigrateCommand;
use App\Models\Address;
use App\Models\Category;
use App\Models\City;
use App\Models\Company;
use App\Models\Country;
use App\Models\Customer;
use App\Models\Order;
use App\Models\OrderDetail;
use App\Models\OrderNote;
use App\Models\Product;
use App\Models\State;
use App\Models\StatusOrder;
use App\Models\Subscription;
use App\Models\User;
use App\Models\Vendor;
use App\Models\WarehouseUser;
use App\Observers\CompanyStripeCustomerObserver;
use App\Observers\CustomerCustIdKorObserver;
use App\Observers\CustomerOrderProductFifoObserver;
use App\Observers\MobileChangeLogObserver;
use App\Observers\SubscriptionCompanyLimitsObserver;
use App\Observers\SubscriptionInvoiceObserver;
use Illuminate\Database\Console\Migrations\MigrateCommand;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Schema;
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

        // Producción puede no tener deleted_at en tablas que el listado de órdenes eager-load.
        // Sin esto el SELECT revienta y /company/orders queda en cero.
        $this->dropSoftDeleteScopeWhenColumnMissing([
            Address::class,
            City::class,
            Country::class,
            Customer::class,
            OrderDetail::class,
            OrderNote::class,
            State::class,
            StatusOrder::class,
            Vendor::class,
            WarehouseUser::class,
        ]);

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

    /**
     * @param  array<int, class-string<Model>>  $models
     */
    private function dropSoftDeleteScopeWhenColumnMissing(array $models): void
    {
        foreach ($models as $class) {
            try {
                if (! in_array(SoftDeletes::class, class_uses_recursive($class), true)) {
                    continue;
                }
                $model = new $class();
                if (Schema::hasColumn($model->getTable(), $model->getDeletedAtColumn())) {
                    continue;
                }
                $boot = new \ReflectionMethod($class, 'bootIfNotBooted');
                $boot->setAccessible(true);
                $boot->invoke(null);

                $prop = new \ReflectionProperty(Model::class, 'globalScopes');
                $prop->setAccessible(true);
                $scopes = $prop->getValue();
                unset($scopes[$class][SoftDeletingScope::class]);
                $prop->setValue(null, $scopes);
            } catch (\Throwable $e) {
                Log::warning('dropSoftDeleteScopeWhenColumnMissing skipped', [
                    'model' => $class,
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }
}
