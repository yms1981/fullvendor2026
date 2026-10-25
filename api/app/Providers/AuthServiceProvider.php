<?php

namespace App\Providers;

use App\Models\Admin;
use App\Policies\AdminPolicy;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The policy mappings for the application.
     *
     * @var array<class-string, class-string>
     */
    protected $policies = [
        Admin::class => AdminPolicy::class,
        'App\Models\AppUpdate' => 'App\Policies\AppUpdatePolicy',
        'App\Models\Banner' => 'App\Policies\BannerPolicy',
        'App\Models\Cache' => 'App\Policies\CachePolicy',
        'App\Models\CacheLock' => 'App\Policies\CacheLockPolicy',
        'App\Models\CartImage' => 'App\Policies\CartImagePolicy',
        'App\Models\Catalog' => 'App\Policies\CatalogPolicy',
        'App\Models\CatalogCart' => 'App\Policies\CatalogCartPolicy',
        'App\Models\CatalogCategory' => 'App\Policies\CatalogCategoryPolicy',
        'App\Models\CatalogProduct' => 'App\Policies\CatalogProductPolicy',
        'App\Models\CatalogRequest' => 'App\Policies\CatalogRequestPolicy',
        'App\Models\CatalogSentMail' => 'App\Policies\CatalogSentMailPolicy',
        'App\Models\Category' => 'App\Policies\CategoryPolicy',
        'App\Models\CategoryCount' => 'App\Policies\CategoryCountPolicy',
        'App\Models\CategoryList' => 'App\Policies\CategoryListPolicy',
        'App\Models\CategoryProduct' => 'App\Policies\CategoryProductPolicy',
        'App\Models\ChangesApp' => 'App\Policies\ChangesAppPolicy',
        'App\Models\City' => 'App\Policies\CityPolicy',
        'App\Models\Cm' => 'App\Policies\CmPolicy',
        'App\Models\CompaniesCategory' => 'App\Policies\CompaniesCategoryPolicy',
        'App\Models\Company' => 'App\Policies\CompanyPolicy',
        'App\Models\CompanyContact' => 'App\Policies\CompanyContactPolicy',
        'App\Models\CompanyStatusOrder' => 'App\Policies\CompanyStatusOrderPolicy',
        'App\Models\CompanyUser' => 'App\Policies\CompanyUserPolicy',
        'App\Models\Consoleconfig' => 'App\Policies\ConsoleconfigPolicy',
        'App\Models\Consoleuser' => 'App\Policies\ConsoleuserPolicy',
        'App\Models\Country' => 'App\Policies\CountryPolicy',
        'App\Models\Currency' => 'App\Policies\CurrencyPolicy',
        'App\Models\CurrencyCode' => 'App\Policies\CurrencyCodePolicy',
        'App\Models\CurrencyFormat' => 'App\Policies\CurrencyFormatPolicy',
        'App\Models\Currencybycountry' => 'App\Policies\CurrencybycountryPolicy',
        'App\Models\Customer' => 'App\Policies\CustomerPolicy',
        'App\Models\CustomerGroup' => 'App\Policies\CustomerGroupPolicy',
        'App\Models\CustomerSeller' => 'App\Policies\CustomerSellerPolicy',
        'App\Models\CustomerUser' => 'App\Policies\CustomerUserPolicy',
        'App\Models\DeliveredOrder' => 'App\Policies\DeliveredOrderPolicy',
        'App\Models\DetailCar' => 'App\Policies\DetailCarPolicy',
        'App\Models\Devicebycompanylist' => 'App\Policies\DevicebycompanylistPolicy',
        'App\Models\Deviceinfo' => 'App\Policies\DeviceinfoPolicy',
        'App\Models\DevicesCompanytemp' => 'App\Policies\DevicesCompanytempPolicy',
        'App\Models\DictionaryGp' => 'App\Policies\DictionaryGpPolicy',
        'App\Models\Enquiry' => 'App\Policies\EnquiryPolicy',
        'App\Models\FormatDate' => 'App\Policies\FormatDatePolicy',
        'App\Models\FormatNumeric' => 'App\Policies\FormatNumericPolicy',
        'App\Models\GetImagesproduct' => 'App\Policies\GetImagesproductPolicy',
        'App\Models\Gpslog' => 'App\Policies\GpslogPolicy',
        'App\Models\Gpstracker' => 'App\Policies\GpstrackerPolicy',
        'App\Models\ImagesCount' => 'App\Policies\ImagesCountPolicy',
        'App\Models\ImagesDefault' => 'App\Policies\ImagesDefaultPolicy',
        'App\Models\ImagesProduct' => 'App\Policies\ImagesProductPolicy',
        'App\Models\ImgNodatum' => 'App\Policies\ImgNodatumPolicy',
        'App\Models\ImplodeImg' => 'App\Policies\ImplodeImgPolicy',
        'App\Models\ImportHistory' => 'App\Policies\ImportHistoryPolicy',
        'App\Models\Invoice' => 'App\Policies\InvoicePolicy',
        'App\Models\InitialOrderNumber' => 'App\Policies\InitialOrderNumberPolicy',
        'App\Models\Inventory' => 'App\Policies\InventoryPolicy',
        'App\Models\InventoryOrder' => 'App\Policies\InventoryOrderPolicy',
        'App\Models\Job' => 'App\Policies\JobPolicy',
        'App\Models\EntidadEmisora' => 'App\Policies\EntidadEmisoraPolicy',
        'App\Models\JobBatch' => 'App\Policies\JobBatchPolicy',
        'App\Models\Key' => 'App\Policies\KeyPolicy',
        'App\Models\Language' => 'App\Policies\LanguagePolicy',
        'App\Models\Log' => 'App\Policies\LogPolicy',
        'App\Models\MailsLog' => 'App\Policies\MailsLogPolicy',
        'App\Models\Myorder' => 'App\Policies\MyorderPolicy',
        'App\Models\Navbar' => 'App\Policies\NavbarPolicy',
        'App\Models\NavbarRolId' => 'App\Policies\NavbarRolIdPolicy',
        'App\Models\Newsletter' => 'App\Policies\NewsletterPolicy',
        'App\Models\Normalization' => 'App\Policies\NormalizationPolicy',
        'App\Models\ODetailList' => 'App\Policies\ODetailListPolicy',
        'App\Models\Order' => 'App\Policies\OrderPolicy',
        'App\Models\OrderCategory' => 'App\Policies\OrderCategoryPolicy',
        'App\Models\OrderDetail' => 'App\Policies\OrderDetailPolicy',
        'App\Models\OrderStatusHistory' => 'App\Policies\OrderStatusHistoryPolicy',
        'App\Models\OrdersCategory' => 'App\Policies\OrdersCategoryPolicy',
        'App\Models\Plan' => 'App\Policies\PlanPolicy',
        'App\Models\ProdSeparado' => 'App\Policies\ProdSeparadoPolicy',
        'App\Models\Product' => 'App\Policies\ProductPolicy',
        'App\Models\ProductImage' => 'App\Policies\ProductImagePolicy',
        'App\Models\ProductsListadoActivo' => 'App\Policies\ProductsListadoActivoPolicy',
        'App\Models\ManagerProfiles' => 'App\Policies\ManagerProfilesPolicy',
        'App\Models\Rols' => 'App\Policies\RolsPolicy',
        'App\Models\RsOrder' => 'App\Policies\RsOrderPolicy',
        'App\Models\RsOrders1' => 'App\Policies\RsOrders1Policy',
        'App\Models\RsOrders2' => 'App\Policies\RsOrders2Policy',
        'App\Models\RsRequested' => 'App\Policies\RsRequestedPolicy',
        'App\Models\Subscription' => 'App\Policies\SubscriptionPolicy',
        'App\Models\SessionCart' => 'App\Policies\SessionCartPolicy',
        'App\Models\Setting' => 'App\Policies\SettingPolicy',
        'App\Models\State' => 'App\Policies\StatePolicy',
        'App\Models\StatusCart' => 'App\Policies\StatusCartPolicy',
        'App\Models\StatusOrder' => 'App\Policies\StatusOrderPolicy',
        'App\Models\StatusRol' => 'App\Policies\StatusRolPolicy',
        'App\Models\TbInventory' => 'App\Policies\TbInventoryPolicy',
        'App\Models\TermsOfSale' => 'App\Policies\TermsOfSalePolicy',
        'App\Models\Unit' => 'App\Policies\UnitPolicy',
        'App\Models\User' => 'App\Policies\UserPolicy',
        'App\Models\SellerProfiles' => 'App\Policies\SellerProfilesPolicy',
        'App\Models\ValidImg' => 'App\Policies\ValidImgPolicy',
        'App\Models\Vendor' => 'App\Policies\VendorPolicy',
        'App\Models\Version' => 'App\Policies\VersionPolicy',
        'App\Models\Versione' => 'App\Policies\VersionePolicy',
        'App\Models\VwInventory' => 'App\Policies\VwInventoryPolicy',
        'App\Models\WarehouseUser' => 'App\Policies\WarehouseUserPolicy',
        'App\Models\Warehouseorder' => 'App\Policies\WarehouseorderPolicy',
        'App\Models\WordersList' => 'App\Policies\WordersListPolicy'
    ];

    /**
     * Register services.
     */
    public function register(): void
    {
        parent::register();
    }
}
