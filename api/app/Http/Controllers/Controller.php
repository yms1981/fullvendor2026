<?php

namespace App\Http\Controllers;


use App\Models\CompositeKeyModel;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasOneOrMany;
use Illuminate\Database\Eloquent\Relations\Pivot;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Http\Kernel;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Log;
use OpenApi\Attributes as OA;
use Symfony\Component\HttpFoundation\Response;


#[
    OA\Info(version: "2.0.0", description: "Fullvendor api", title: "Fullvendor APi Documentation"),
    OA\Server(url: 'http://localhost', description: "local (proxy MVC :80)"),
    OA\Server(url: 'http://localhost:81', description: "local Sail directo (:81)"),
    OA\Server(url: 'https://test.fullvendor.com', description: "test server"),
    //OA\Server(url: 'http://staging.fullvendor.com', description: "Staging server"),
    OA\Server(url: 'https://demo.fullvendor.com', description: "Demo server"),
    OA\Server(url: 'https://app.fullvendor.com', description: "production server"),
    OA\SecurityScheme(securityScheme: 'bearerAuth', type: "http", name: "Authorization", in: "header", scheme: "bearer"),
    OA\SecurityScheme(securityScheme: 'erpApiKey', type: "apiKey", name: "X-Api-Key", in: "header", description: "Token fijo ERP (config ERP_API_KEY)"),
]
abstract class Controller
{
    use AuthorizesRequests, ValidatesRequests;

    protected $resourceRoute;

    protected $defaultOrderDirection='ASC';
    protected $defaultOrderProperty;

    /** Orden secundario opcional (p. ej. orders: updated DESC, created DESC). */
    protected $defaultOrderPropertySecondary = '';
    protected $defaultOrderDirectionSecondary = 'DESC';

    protected $perPage=30;

    /**
     * @param $resourceBaseRoute
     * @return JsonResource|string
     */
    public static function getResourceForRoute($resourceBaseRoute)
    {
        return self::$apiResources[$resourceBaseRoute]['resource'];
    }

    /**
     * @param $resourceBaseRoute
     * @return Model|string|\Eloquent
     */
    public static function getModelForRoute($resourceBaseRoute)
    {
        return self::$apiResources[$resourceBaseRoute]['model'];
    }

    /**
     * @param $resourceBaseRoute
     * @return string|Controller
     */
    public static function getControllerForRoute($resourceBaseRoute)
    {
        return self::$apiResources[$resourceBaseRoute]['controller'];
    }

    protected static $apiResources = [
        'admin' => [
            'model' => \App\Models\Admin::class,
            'resource' => \App\Http\Resources\AdminResource::class,
            'controller' => \App\Http\Controllers\AdminController::class
        ],

        'appupdate' => [
            'model' => \App\Models\AppUpdate::class,
            'resource' => \App\Http\Resources\AppUpdateResource::class,
            'controller' => \App\Http\Controllers\AppUpdateController::class
        ],

        'banner' => [
            'model' => \App\Models\Banner::class,
            'resource' => \App\Http\Resources\BannerResource::class,
            'controller' => \App\Http\Controllers\BannerController::class
        ],

        'cachelock' => [
            'model' => \App\Models\CacheLock::class,
            'resource' => \App\Http\Resources\CacheLockResource::class,
            'controller' => \App\Http\Controllers\CacheLockController::class
        ],

        'cache' => [
            'model' => \App\Models\Cache::class,
            'resource' => \App\Http\Resources\CacheResource::class,
            'controller' => \App\Http\Controllers\CacheController::class
        ],


        'catalogcart' => [
            'model' => \App\Models\CatalogCart::class,
            'resource' => \App\Http\Resources\CatalogCartResource::class,
            'controller' => \App\Http\Controllers\Company\CatalogCartController::class
        ],

        'catalog' => [
            'model' => \App\Models\Catalog::class,
            'resource' => \App\Http\Resources\CatalogResource::class,
            'controller' => \App\Http\Controllers\Company\CatalogController::class
        ],

        'catalogrequest' => [
            'model' => \App\Models\CatalogRequest::class,
            'resource' => \App\Http\Resources\CatalogRequestResource::class,
            'controller' => \App\Http\Controllers\Company\CatalogRequestController::class
        ],

        'catalogsentmail' => [
            'model' => \App\Models\CatalogSentMail::class,
            'resource' => \App\Http\Resources\CatalogSentMailResource::class,
            'controller' => \App\Http\Controllers\Company\CatalogSentMailController::class
        ],

        'categorycount' => [
            'model' => \App\Models\CategoryCount::class,
            'resource' => \App\Http\Resources\CategoryCountResource::class,
            'controller' => \App\Http\Controllers\CategoryCountController::class
        ],

        'categorylist' => [
            'model' => \App\Models\CategoryList::class,
            'resource' => \App\Http\Resources\CategoryListResource::class,
            'controller' => \App\Http\Controllers\CategoryListController::class
        ],

        'category' => [
            'model' => \App\Models\Category::class,
            'resource' => \App\Http\Resources\CategoryResource::class,
            'controller' => \App\Http\Controllers\CategoryController::class
        ],

        'changesapp' => [
            'model' => \App\Models\ChangesApp::class,
            'resource' => \App\Http\Resources\ChangesAppResource::class,
            'controller' => \App\Http\Controllers\ChangesAppController::class
        ],

        'city' => [
            'model' => \App\Models\City::class,
            'resource' => \App\Http\Resources\CityResource::class,
            'controller' => \App\Http\Controllers\CityController::class
        ],

        'cm' => [
            'model' => \App\Models\Cm::class,
            'resource' => \App\Http\Resources\CmResource::class,
            'controller' => \App\Http\Controllers\CmController::class
        ],

        'companiescategory' => [
            'model' => \App\Models\CompaniesCategory::class,
            'resource' => \App\Http\Resources\CompaniesCategoryResource::class,
            'controller' => \App\Http\Controllers\CompaniesCategoryController::class
        ],

        'companycontact' => [
            'model' => \App\Models\CompanyContact::class,
            'resource' => \App\Http\Resources\CompanyContactResource::class,
            'controller' => \App\Http\Controllers\CompanyContactController::class
        ],

        'company' => [
            'model' => \App\Models\Company::class,
            'resource' => \App\Http\Resources\CompanyResource::class,
            'controller' => \App\Http\Controllers\CompanyController::class
        ],

        'companystatusorder' => [
            'model' => \App\Models\CompanyStatusOrder::class,
            'resource' => \App\Http\Resources\CompanyStatusOrderResource::class,
            'controller' => \App\Http\Controllers\CompanyStatusOrderController::class
        ],

        'consoleconfig' => [
            'model' => \App\Models\Consoleconfig::class,
            'resource' => \App\Http\Resources\ConsoleconfigResource::class,
            'controller' => \App\Http\Controllers\ConsoleconfigController::class
        ],

        'consoleuser' => [
            'model' => \App\Models\Consoleuser::class,
            'resource' => \App\Http\Resources\ConsoleuserResource::class,
            'controller' => \App\Http\Controllers\ConsoleuserController::class
        ],

        'country' => [
            'model' => \App\Models\Country::class,
            'resource' => \App\Http\Resources\CountryResource::class,
            'controller' => \App\Http\Controllers\CountryController::class
        ],

        'currencyformat' => [
            'model' => \App\Models\CurrencyFormat::class,
            'resource' => \App\Http\Resources\CurrencyFormatResource::class,
            'controller' => \App\Http\Controllers\CurrencyFormatController::class
        ],

        'currency' => [
            'model' => \App\Models\Currency::class,
            'resource' => \App\Http\Resources\CurrencyResource::class,
            'controller' => \App\Http\Controllers\CurrencyController::class
        ],

        'customergroup' => [
            'model' => \App\Models\CustomerGroup::class,
            'resource' => \App\Http\Resources\CustomerGroupResource::class,
            'controller' => \App\Http\Controllers\CustomerGroupController::class
        ],

        'customer' => [
            'model' => \App\Models\Customer::class,
            'resource' => \App\Http\Resources\CustomerResource::class,
            'controller' => \App\Http\Controllers\CustomerController::class
        ],

        'deliveredorder' => [
            'model' => \App\Models\DeliveredOrder::class,
            'resource' => \App\Http\Resources\DeliveredOrderResource::class,
            'controller' => \App\Http\Controllers\DeliveredOrderController::class
        ],

        'detailcar' => [
            'model' => \App\Models\DetailCar::class,
            'resource' => \App\Http\Resources\DetailCarResource::class,
            'controller' => \App\Http\Controllers\DetailCarController::class
        ],

        'devicebycompanylist' => [
            'model' => \App\Models\Devicebycompanylist::class,
            'resource' => \App\Http\Resources\DevicebycompanylistResource::class,
            'controller' => \App\Http\Controllers\DevicebycompanylistController::class
        ],

        'deviceinfo' => [
            'model' => \App\Models\Deviceinfo::class,
            'resource' => \App\Http\Resources\DeviceinfoResource::class,
            'controller' => \App\Http\Controllers\DeviceinfoController::class
        ],

        'devicescompanytemp' => [
            'model' => \App\Models\DevicesCompanytemp::class,
            'resource' => \App\Http\Resources\DevicesCompanytempResource::class,
            'controller' => \App\Http\Controllers\DevicesCompanytempController::class
        ],

        'dictionarygp' => [
            'model' => \App\Models\DictionaryGp::class,
            'resource' => \App\Http\Resources\DictionaryGpResource::class,
            'controller' => \App\Http\Controllers\DictionaryGpController::class
        ],

        'enquiry' => [
            'model' => \App\Models\Enquiry::class,
            'resource' => \App\Http\Resources\EnquiryResource::class,
            'controller' => \App\Http\Controllers\EnquiryController::class
        ],

        'formatdate' => [
            'model' => \App\Models\FormatDate::class,
            'resource' => \App\Http\Resources\FormatDateResource::class,
            'controller' => \App\Http\Controllers\FormatDateController::class
        ],

        'formatnumeric' => [
            'model' => \App\Models\FormatNumeric::class,
            'resource' => \App\Http\Resources\FormatNumericResource::class,
            'controller' => \App\Http\Controllers\FormatNumericController::class
        ],

        'getimagesproduct' => [
            'model' => \App\Models\GetImagesproduct::class,
            'resource' => \App\Http\Resources\GetImagesproductResource::class,
            'controller' => \App\Http\Controllers\GetImagesproductController::class
        ],

        'gpslog' => [
            'model' => \App\Models\Gpslog::class,
            'resource' => \App\Http\Resources\GpslogResource::class,
            'controller' => \App\Http\Controllers\GpslogController::class
        ],

        'gpstracker' => [
            'model' => \App\Models\Gpstracker::class,
            'resource' => \App\Http\Resources\GpstrackerResource::class,
            'controller' => \App\Http\Controllers\GpstrackerController::class
        ],

        'imagescount' => [
            'model' => \App\Models\ImagesCount::class,
            'resource' => \App\Http\Resources\ImagesCountResource::class,
            'controller' => \App\Http\Controllers\ImagesCountController::class
        ],

        'imagesdefault' => [
            'model' => \App\Models\ImagesDefault::class,
            'resource' => \App\Http\Resources\ImagesDefaultResource::class,
            'controller' => \App\Http\Controllers\ImagesDefaultController::class
        ],

        'imagesproduct' => [
            'model' => \App\Models\ImagesProduct::class,
            'resource' => \App\Http\Resources\ImagesProductResource::class,
            'controller' => \App\Http\Controllers\ImagesProductController::class
        ],

        'imgnodatum' => [
            'model' => \App\Models\ImgNodatum::class,
            'resource' => \App\Http\Resources\ImgNodatumResource::class,
            'controller' => \App\Http\Controllers\ImgNodatumController::class
        ],

        'implodeimg' => [
            'model' => \App\Models\ImplodeImg::class,
            'resource' => \App\Http\Resources\ImplodeImgResource::class,
            'controller' => \App\Http\Controllers\ImplodeImgController::class
        ],

        'importhistory' => [
            'model' => \App\Models\ImportHistory::class,
            'resource' => \App\Http\Resources\ImportHistoryResource::class,
            'controller' => \App\Http\Controllers\ImportHistoryController::class
        ],

        'initialordernumber' => [
            'model' => \App\Models\InitialOrderNumber::class,
            'resource' => \App\Http\Resources\InitialOrderNumberResource::class,
            'controller' => \App\Http\Controllers\InitialOrderNumberController::class
        ],

        'invoice' => [
            'model' => \App\Models\Invoice::class,
            'resource' => \App\Http\Resources\InvoiceResource::class,
            'controller' => \App\Http\Controllers\InvoiceController::class
        ],

        'inventoryorder' => [
            'model' => \App\Models\InventoryOrder::class,
            'resource' => \App\Http\Resources\InventoryOrderResource::class,
            'controller' => \App\Http\Controllers\InventoryOrderController::class
        ],

        'inventory' => [
            'model' => \App\Models\Inventory::class,
            'resource' => \App\Http\Resources\InventoryResource::class,
            'controller' => \App\Http\Controllers\InventoryController::class
        ],

        'jobbatch' => [
            'model' => \App\Models\JobBatch::class,
            'resource' => \App\Http\Resources\JobBatchResource::class,
            'controller' => \App\Http\Controllers\JobBatchController::class
        ],

        'job' => [
            'model' => \App\Models\Job::class,
            'resource' => \App\Http\Resources\JobResource::class,
            'controller' => \App\Http\Controllers\JobController::class
        ],

        'key' => [
            'model' => \App\Models\Key::class,
            'resource' => \App\Http\Resources\KeyResource::class,
            'controller' => \App\Http\Controllers\KeyController::class
        ],

        'language' => [
            'model' => \App\Models\Language::class,
            'resource' => \App\Http\Resources\LanguageResource::class,
            'controller' => \App\Http\Controllers\LanguageController::class
        ],

        'log' => [
            'model' => \App\Models\Log::class,
            'resource' => \App\Http\Resources\LogResource::class,
            'controller' => \App\Http\Controllers\LogController::class
        ],

        'mailslog' => [
            'model' => \App\Models\MailsLog::class,
            'resource' => \App\Http\Resources\MailsLogResource::class,
            'controller' => \App\Http\Controllers\MailsLogController::class
        ],


        'navbar' => [
            'model' => \App\Models\Navbar::class,
            'resource' => \App\Http\Resources\NavbarResource::class,
            'controller' => \App\Http\Controllers\NavbarController::class
        ],

        'navbarrolid' => [
            'model' => \App\Models\NavbarRolId::class,
            'resource' => \App\Http\Resources\NavbarRolIdResource::class,
            'controller' => \App\Http\Controllers\NavbarRolIdController::class
        ],

        'newsletter' => [
            'model' => \App\Models\Newsletter::class,
            'resource' => \App\Http\Resources\NewsletterResource::class,
            'controller' => \App\Http\Controllers\NewsletterController::class
        ],

        'odetaillist' => [
            'model' => \App\Models\ODetailList::class,
            'resource' => \App\Http\Resources\ODetailListResource::class,
            'controller' => \App\Http\Controllers\ODetailListController::class
        ],

        'ordercategory' => [
            'model' => \App\Models\OrderCategory::class,
            'resource' => \App\Http\Resources\OrderCategoryResource::class,
            'controller' => \App\Http\Controllers\OrderCategoryController::class
        ],

        'orderdetail' => [
            'model' => \App\Models\OrderDetail::class,
            'resource' => \App\Http\Resources\OrderDetailResource::class,
            'controller' => \App\Http\Controllers\OrderDetailController::class
        ],

        'order' => [
            'model' => \App\Models\Order::class,
            'resource' => \App\Http\Resources\OrderResource::class,
            'controller' => \App\Http\Controllers\OrderController::class
        ],
        'orderflows' => [
            'model' => \App\Models\OrderFlow::class,
            'resource' => \App\Http\Resources\OrderFlowResource::class,
            'controller' => \App\Http\Controllers\OrderFlowController::class
        ],

        'orderflowtransitions' => [
            'model' => \App\Models\OrderFlowTransition::class,
            'resource' => \App\Http\Resources\OrderFlowTransitionResource::class,
            'controller' => \App\Http\Controllers\OrderFlowTransitionController::class
        ],

        'orderstatushistory' => [
            'model' => \App\Models\OrderStatusHistory::class,
            'resource' => \App\Http\Resources\OrderStatusHistoryResource::class,
            'controller' => \App\Http\Controllers\OrderStatusHistoryController::class
        ],

        'plan' => [
            'model' => \App\Models\Plan::class,
            'resource' => \App\Http\Resources\PlanResource::class,
            'controller' => \App\Http\Controllers\PlanController::class
        ],

        'prodseparado' => [
            'model' => \App\Models\ProdSeparado::class,
            'resource' => \App\Http\Resources\ProdSeparadoResource::class,
            'controller' => \App\Http\Controllers\ProdSeparadoController::class
        ],

        'productimage' => [
            'model' => \App\Models\ProductImage::class,
            'resource' => \App\Http\Resources\ProductImageResource::class,
            'controller' => \App\Http\Controllers\ProductImageController::class
        ],

        'product' => [
            'model' => \App\Models\Product::class,
            'resource' => \App\Http\Resources\ProductResource::class,
            'controller' => \App\Http\Controllers\ProductController::class
        ],

        'productslistadoactivo' => [
            'model' => \App\Models\ProductsListadoActivo::class,
            'resource' => \App\Http\Resources\ProductsListadoActivoResource::class,
            'controller' => \App\Http\Controllers\ProductsListadoActivoController::class
        ],

        'prorepetido' => [
            'model' => \App\Models\ProRepetido::class,
            'resource' => \App\Http\Resources\ProRepetidoResource::class,
            'controller' => \App\Http\Controllers\ProRepetidoController::class
        ],

        'managerprofiles' => [
            'model' => \App\Models\ManagerProfiles::class,
            'resource' => \App\Http\Resources\ManagerProfilesResource::class,
            'controller' => \App\Http\Controllers\ManagerProfilesController::class
        ],

        'rols' => [
            'model' => \App\Models\Rols::class,
            'resource' => \App\Http\Resources\RolsResource::class,
            'controller' => \App\Http\Controllers\RolsController::class
        ],

        'rsorder' => [
            'model' => \App\Models\RsOrder::class,
            'resource' => \App\Http\Resources\RsOrderResource::class,
            'controller' => \App\Http\Controllers\RsOrderController::class
        ],

        'rsorders1' => [
            'model' => \App\Models\RsOrders1::class,
            'resource' => \App\Http\Resources\RsOrders1Resource::class,
            'controller' => \App\Http\Controllers\RsOrders1Controller::class
        ],

        'rsorders2' => [
            'model' => \App\Models\RsOrders2::class,
            'resource' => \App\Http\Resources\RsOrders2Resource::class,
            'controller' => \App\Http\Controllers\RsOrders2Controller::class
        ],

        'rsrequested' => [
            'model' => \App\Models\RsRequested::class,
            'resource' => \App\Http\Resources\RsRequestedResource::class,
            'controller' => \App\Http\Controllers\RsRequestedController::class
        ],

        'session' => [
            'model' => \App\Models\Session::class,
            'resource' => \App\Http\Resources\SessionResource::class,
            'controller' => \App\Http\Controllers\SessionController::class
        ],

        'setting' => [
            'model' => \App\Models\Setting::class,
            'resource' => \App\Http\Resources\SettingResource::class,
            'controller' => \App\Http\Controllers\SettingController::class
        ],

        'state' => [
            'model' => \App\Models\State::class,
            'resource' => \App\Http\Resources\StateResource::class,
            'controller' => \App\Http\Controllers\StateController::class
        ],

        'statuscart' => [
            'model' => \App\Models\StatusCart::class,
            'resource' => \App\Http\Resources\StatusCartResource::class,
            'controller' => \App\Http\Controllers\StatusCartController::class
        ],

        'statusorder' => [
            'model' => \App\Models\StatusOrder::class,
            'resource' => \App\Http\Resources\StatusOrderResource::class,
            'controller' => \App\Http\Controllers\StatusOrderController::class
        ],

        'subscription' => [
            'model' => \App\Models\Subscription::class,
            'resource' => \App\Http\Resources\SubscriptionResource::class,
            'controller' => \App\Http\Controllers\SubscriptionController::class
        ],

        'statusrol' => [
            'model' => \App\Models\StatusRol::class,
            'resource' => \App\Http\Resources\StatusRolResource::class,
            'controller' => \App\Http\Controllers\StatusRolController::class
        ],

        'tbinventory' => [
            'model' => \App\Models\TbInventory::class,
            'resource' => \App\Http\Resources\TbInventoryResource::class,
            'controller' => \App\Http\Controllers\TbInventoryController::class
        ],

        'termsofsale' => [
            'model' => \App\Models\TermsOfSale::class,
            'resource' => \App\Http\Resources\TermsOfSaleResource::class,
            'controller' => \App\Http\Controllers\TermsOfSaleController::class
        ],

        'unit' => [
            'model' => \App\Models\Unit::class,
            'resource' => \App\Http\Resources\UnitResource::class,
            'controller' => \App\Http\Controllers\UnitController::class
        ],

        'user' => [
            'model' => \App\Models\User::class,
            'resource' => \App\Http\Resources\UserResource::class,
            'controller' => \App\Http\Controllers\UserController::class
        ],

        'sellerprofiles' => [
            'model' => \App\Models\SellerProfiles::class,
            'resource' => \App\Http\Resources\SellerProfilesResource::class,
            'controller' => \App\Http\Controllers\SellerProfilesController::class
        ],

        'validimg' => [
            'model' => \App\Models\ValidImg::class,
            'resource' => \App\Http\Resources\ValidImgResource::class,
            'controller' => \App\Http\Controllers\ValidImgController::class
        ],

        'vendor' => [
            'model' => \App\Models\Vendor::class,
            'resource' => \App\Http\Resources\VendorResource::class,
            'controller' => \App\Http\Controllers\VendorController::class
        ],

        'versione' => [
            'model' => \App\Models\Versione::class,
            'resource' => \App\Http\Resources\VersioneResource::class,
            'controller' => \App\Http\Controllers\VersioneController::class
        ],

        'version' => [
            'model' => \App\Models\Version::class,
            'resource' => \App\Http\Resources\VersionResource::class,
            'controller' => \App\Http\Controllers\VersionController::class
        ],

        'vwinventory' => [
            'model' => \App\Models\VwInventory::class,
            'resource' => \App\Http\Resources\VwInventoryResource::class,
            'controller' => \App\Http\Controllers\VwInventoryController::class
        ],

        'warehouseorder' => [
            'model' => \App\Models\Warehouseorder::class,
            'resource' => \App\Http\Resources\WarehouseorderResource::class,
            'controller' => \App\Http\Controllers\WarehouseorderController::class
        ],

        'warehouse' => [
            'model' => \App\Models\Warehouse::class,
            'resource' => \App\Http\Resources\WarehouseResource::class,
            'controller' => \App\Http\Controllers\WarehouseController::class
        ],

        'warehouseuser' => [
            'model' => \App\Models\WarehouseUser::class,
            'resource' => \App\Http\Resources\WarehouseUserResource::class,
            'controller' => \App\Http\Controllers\WarehouseUserController::class
        ],

        'worderslist' => [
            'model' => \App\Models\WordersList::class,
            'resource' => \App\Http\Resources\WordersListResource::class,
            'controller' => \App\Http\Controllers\WordersListController::class
        ],
    ];

    protected function getModelList(Request $request=null) {
        if(!isset($request)) $request = request();

        $nonPaginated=$request->input('all',false);
        $columns = $this->getColumns($request->input('props',['*']));

        $filteredSortedModelQuery = $this->getFilteredSortedModelQuery($request);

        if($nonPaginated) {
            set_time_limit(0);
            return $filteredSortedModelQuery->get($columns);
        }

        return $this->getPaginatedModelList($filteredSortedModelQuery, $columns, $request);
    }

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return $this->getModelList();
    }

    /**
     * Resolver el modelo desde `$apiResources`.
     *
     * @param string $model
     * @return string
     * @throws \Exception
     */
    protected function resolveApiModelClass($model)
    {
        if (!isset(static::$apiResources[$model]['model'])) {
            throw new \Exception("Modelo no configurado para \"$model\" en \$apiResources.");
        }

        return static::$apiResources[$model]['model'];
    }

    /**
     * @param Model|string $model
     * @return Model
     */
    protected function getMainModel($model=null) {
        $request = \request();
        $realModel = isset($model)?$this->resolveApiModelClass($model)::query():self::getModelForRoute($this->resourceRoute)::query();

        $with = $request->input('relations',[]);
        if(!empty($with))
            $realModel->with($with);
        return $realModel;
    }

    /**
     * @param Model|string $model
     * @return Builder|\Illuminate\Database\Query\Builder
     */
    protected function getBuilder($model = null)
    {
        return $this->getMainModel($model)->newQuery();
    }

    /**
     * @param string|array $properties
     * @return array
     */
    protected function getColumns($properties) {
        return is_string($properties)?explode(',',$properties):$properties;
    }

    /**
     * Gets the query builder for the model of the controller resource filtered with the request inputs thrugh the ModelFilter
     * @param Request $request
     * @return mixed
     */
    protected function getFilteredModelQuery(Request $request) {
        return $this->getBuilder()->filter($request->all());
    }

    /**
     * @param Request $request
     * @return Builder
     */
    protected function getFilteredSortedModelQuery(Request $request): Builder
    {
        /** @var $query Builder */
        $query = $this->getFilteredModelQuery($request);

        $sortColumn = $request->input('orderProperty', $this->defaultOrderProperty);
        $sortDirection = $request->input('orderDirection', $this->defaultOrderDirection);

        if (!empty($sortColumn)) {
            $query->orderBy($sortColumn, $sortDirection);
        }

        $secondaryColumn = (string) $this->defaultOrderPropertySecondary;
        if ($secondaryColumn !== '' && $secondaryColumn !== (string) $sortColumn) {
            $query->orderBy(
                $secondaryColumn,
                (string) ($this->defaultOrderDirectionSecondary ?: 'DESC')
            );
        }

        return $query;
    }

    /**
     * Gets a paginated, sorted, filtered list of the models of the controller resource from route
     * @param Builder $filteredSortedModelQuery
     * @param array $columns
     * @param Request $request
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator
     */
    protected function getPaginatedModelList(
        Builder $filteredSortedModelQuery,
        array $columns,
        Request $request
    )
    {
        $perPage = $request->input('perPage', $this->perPage);
       $page = $request->input('page', 1);

        return $filteredSortedModelQuery->paginate($perPage, $columns,__('page'),$page);
    }

    /**
     * @param $modelId
     * @param Request $request
     * @return \Eloquent|Builder|Model
     */
    protected function getModelInstanceBuilder($modelId,Request $request =null) {
        if(!isset($request)) $request = \request();

        /** @var Model|Builder $modelClass */
        $modelClass = $this->getBuilder($this->resourceRoute);
        $keyName = $modelClass->getModel()->getKeyName();
        return $modelClass->where($keyName, $modelId);
    }

    protected function getMainModelInstance($modelId)
    {
        $columns = request()->input('props',['*']);
        $with = request()->input('relations',[]);

        return $this->getModelInstanceBuilder($modelId)->select($columns)->with($with)->sole();
    }

    /**
     * @param $modelId
     * @param $relatedId
     * @param string $relation
     * @param $listMethod
     * @param array $pivotAttributes
     */
    protected function addRelated($modelId, $relatedId, $relation, $listMethod = null, $pivotAttributes = [])
    {
        if (!isset($listMethod)) {
            $listMethod = $relation;
        }
        $item = $this->getMainModelInstance($modelId);

        /** @var BelongsToMany|HasOneOrMany $rel */
        $rel = $item->$relation();
        /** @var Model|\Eloquent $relatedModel */
        $relatedModel = $rel->getRelated();
        $relatedkeyName = $relatedModel->getKeyName();

        $rel->save($relatedModel::where($relatedkeyName, $relatedId)->sole(), $pivotAttributes);
        return $this->$listMethod($modelId);
    }

    /**
     * @param $modelId
     * @param $relatedId
     * @param string $relation
     * @param $listMethod
     */
    protected function removeRelated($modelId, $relatedId, $relation, $listMethod = null)
    {
        if (!isset($listMethod)) {
            $listMethod = $relation;
        }
        $item = $this->getMainModelInstance($modelId);

        /** @var BelongsToMany|HasOneOrMany $rel */
        $rel = $item->$relation();
        /** @var Model|\Eloquent $relatedModel */
        $relatedModel = $rel->getRelated();
        $relatedkeyName = $relatedModel->getKeyName();

        $delMethod = 'delete';
        if (method_exists($rel, 'detach')) {
            $delMethod = 'detach';
        }

        $rel->$delMethod($relatedModel::where($relatedkeyName, $relatedId)->sole());
        $item->save();
        return $this->$listMethod($modelId);
    }

    protected function makeFromArray($attributes)
    {
        return self::getModelForRoute($this->resourceRoute)::make($attributes);
    }

//    protected function createCompositeKeyedModel($attributes) {
//        /** @var CompositeKeyModel $item */
//        $item = $this->makeFromArray($attributes);
//        foreach ($item->getKeyName() as $subKey) {
//            $item->$subKey = \request()->get($subKey);
//        }
//        if($item->save())
//            return $item->refresh();
//        return null;
//    }

    /**
     * @param $attributes
     * @param bool $returnResource
     * @return JsonResource|Model
     */
    protected function  createFromAttributes($attributes, bool $returnResource = true)
    {
        $modelClass = self::getModelForRoute($this->resourceRoute);
        /*if(is_a($modelClass,CompositeKeyModel::class,true)) {
            $created = $this->createCompositeKeyedModel($attributes);
        }
        else*/

        if (!$modelClass::newModelInstance()->getIncrementing()) {
            $created = $this->makeFromArray($attributes);
            $this->setPrimaryKeyValues($created, $attributes);
            $created->save();
            $created->refresh();
        } else {
            try{
                $created = $modelClass::create($attributes);
            } catch (\PDOException $e) {
                /** @var Model $created */
                $created = \App::make($modelClass);
                foreach ($attributes as $propName => $value) {
                    if (! array_key_exists($propName, $created->getAttributes())) {
                        $created->setAttribute($propName, $value);
                    }
                }
                $created->save();
            }

        }

        return $returnResource?$this->makeResource($created):$created;
    }

    /**
     * @param Model $model
     * @return mixed
     */
    protected function makeResource($model): mixed
    {
        $resourceClass = self::getResourceForRoute($this->resourceRoute);
        return new $resourceClass($model);
    }

    /**
     * @param Model $modelInstance
     * @param array $attributes
     * @return mixed
     */
    protected function updateModel($modelInstance, $attributes)
    {
        $modelInstance->update($attributes);
        return $this->makeResource($modelInstance);
    }

    /**
     * @param Model $item
     * @param bool $force
     * @return \Illuminate\Http\Response|void
     */
    protected function destroyResourceEmptyResponse(Model $item, bool $force = false)
    {
        $pk = $item->getKey();
        $deleteMethod=$force?'forceDelete':'delete';
        if ($item->$deleteMethod()) {
            Log::info("The ".get_class($item)." #".$pk." was deleted by");
            return response()->noContent();
        }

        abort(Response::HTTP_INTERNAL_SERVER_ERROR);
    }

    /**
     * Defines the condition for the queryBuilder to find a model
     * of self::$model class as an array of conditions
     * @param Model $item
     * @param array $attributes
     * @return array[]
     */
    protected function buildKeyFromAttributes($item, $attributes)
    {
        $key = $item->getKeyName();
        $compositeKey = [];
        if (is_string($key)) {
            return [[$key, $attributes[$key]]];
        }

        foreach ($key as $partialKey) {
            $compositeKey[] = [$partialKey, $attributes[$partialKey]];
        }
        return $compositeKey;
    }

    /**
     * @param string $serializedId
     * @return \Illuminate\Database\Eloquent\Model
     */
    protected function getInstanceFromSerialized(string $serializedId)
    {
        /** @var CompositeKeyModel $model */
        $model = Kernel::getModelForRoute($this->resourceRoute)::newModelInstance();
        $compositeIdColumns = $model->getKeyName();
        $unserializedValues = explode($model->getSerializeValueSeparator(), $serializedId);
        $compositeIdCondition = [];
        foreach ($compositeIdColumns as $column) {
            $compositeIdCondition[] = [$column, current($unserializedValues)];
            next($unserializedValues);
        }
        return self::getModelForRoute($this->resourceRoute)::where($compositeIdCondition)->sole();
    }

    /**
     * @param mixed $modelId
     * @param string $relation
     * @param string|JsonResource $resourceClass
     * @return mixed
     */
    protected function listRelated($modelId, $relation, $resourceClass)
    {
        /** @var \Eloquent $modelClass */
        $modelClass = self::getModelForRoute($this->resourceRoute);
        $keyName = $modelClass::getModel()->getKeyName();
        $item = $modelClass::where($keyName, $modelId)->sole();
        return $resourceClass::collection($item->$relation);
    }

    /**
     * @param mixed $modelId
     * @param string $relation
     * @param string|JsonResource $resourceClass
     * @return mixed
     */
    protected function getRelatedOne($modelId, $relation, $resourceClass)
    {
        /** @var \Eloquent $modelClass */
        $modelClass = self::getModelForRoute($this->resourceRoute);
        $keyName = $modelClass::getModel()->getKeyName();
        $item = $modelClass::where($keyName, $modelId)->sole();
        return $item->$relation;
    }

    /**
     * Explicitly sets the primary key value or values
     * @param Model|Pivot
     * @param $attributes
     * @return void
     */
    protected function setPrimaryKeyValues(Model|Pivot $created, $attributes): void
    {
        if (is_a($created, Pivot::class)) {
            $rk = $created->getRelatedKey();
            $fk = $created->getForeignKey();
            $created->{$rk} = $attributes[$rk];
            $created->{$fk} = $attributes[$fk];
        } else {
            $pk = $created->getKeyName();
            $created->{$pk} = $attributes[$pk];
        }
    }


}
