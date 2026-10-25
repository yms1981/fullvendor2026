<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models;

use App\Support\CurrencySchemaHelper;
use Carbon\Carbon;
use EloquentFilter\Filterable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Class Company
 *
 * @property int $company_id
 * @property int|null $company_category_id
 * @property int $language_id
 * @property string|null $name
 * @property string|null $short_name
 * @property string|null $email
 * @property string|null $stripe_id
 * @property string|null $password
 * @property string|null $phone_number
 * @property string|null $cell_number
 * @property string|null $fax
 * @property string|null $image
 * @property string|null $small_image
 * @property string|null $tax_condition
 * @property string|null $tax_id
 * @property float|null $max_number_of_clients
 * @property float|null $max_number_of_orders
 * @property float|null $max_number_of_vendors
 * @property float|null $total_price
 * @property string|null $website
 * @property Carbon|null $created
 * @property int|null $status
 * @property string|null $slug_url
 * @property string|null $currency
 * @property int $createdby_comp_id
 * @property string|null $token
 * @property int|null $ordersend_time
 * @property int|null $catalogsend_time
 * @property int|null $format_id
 * @property int|null $format_date_id
 * @property string|null $deleted_at
 * @property int|null $sell_with_inventory
 * @property int|null $rol_id
 * @property int $show_inventory
 * @property int|null $show_semaforo_stock
 * @property int|null $email_client_on_salesman_order
 * @property int|null $publish_price_list
 * @property int|null $show_plus_taxes_label
 * @property string|null $plus_taxes_label
 * @property string|null $sellers_ids
 * @property int|null $sem_red_hasta
 * @property int|null $sem_amarillo_desde
 * @property int|null $sem_amarillo_hasta
 * @property int|null $sem_verde_desde
 * @property int|null $sem_verde_hasta
 * @property string|null $sem_red
 * @property string|null $sem_amarillo
 * @property string|null $sem_verde
 * @property CompaniesCategory|null $companies_category
 * @property Company $company
 * @property Language $language
 * @property ManagerProfiles|null $rol
 * @property Collection|Catalog[] $catalogs
 * @property Collection|CatalogCart[] $catalog_carts
 * @property Collection|CatalogSentMail[] $catalog_sent_mails
 * @property Collection|Category[] $categories
 * @property Collection|ChangesApp[] $changes_apps
 * @property Collection|Company[] $companies
 * @property Collection|CompanyContact[] $company_contacts
 * @property Collection|CompanyStatusOrder[] $company_status_orders
 * @property Collection|Consoleconfig[] $consoleconfigs
 * @property Collection|Currency[] $currencies
 * @property Collection|CustomerGroup[] $customer_groups
 * @property Collection|Customer[] $customers
 * @property Collection|DetailCar[] $detail_cars
 * @property Collection|ImportHistory[] $import_histories
 * @property Collection|Inventory[] $inventories
 * @property Collection|Log[] $logs
 * @property Collection|NavbarRolId[] $navbar_rol_ids
 * @property Collection|OrderCategory[] $order_categories
 * @property Collection|OrderStatusHistory[] $order_status_histories
 * @property Collection|Order[] $orders
 * @property Collection|ProductImage[] $product_images
 * @property Collection|Product[] $products
 * @property Collection|ManagerProfiles[] $rols
 * @property Collection|SessionCart[] $session_carts
 * @property Collection|StatusRol[] $status_rols
 * @property Collection|TermsOfSale[] $terms_of_sales
 * @property Collection|Unit[] $units
 * @property Collection|SellerProfiles[] $user_profiles
 * @property Collection|Vendor[] $vendors
 * @property Collection|WarehouseUser[] $warehouse_users
 * @property WordersList $worders_list
 * @package App\Models
 * @property int|null $email_on_salesman_order
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property int|null $default_unit_id
 * @property-read int|null $catalog_carts_count
 * @property-read int|null $catalog_sent_mails_count
 * @property-read int|null $catalogs_count
 * @property-read int|null $categories_count
 * @property-read int|null $changes_apps_count
 * @property-read int|null $companies_count
 * @property-read \App\Models\CompaniesCategory|null $company_category
 * @property-read int|null $company_contacts_count
 * @property-read int|null $company_status_orders_count
 * @property-read int|null $consoleconfigs_count
 * @property-read Company|null $createdByCompany
 * @property-read \App\Models\CurrencyFormat|null $currencyCode
 * @property-read int|null $customer_groups_count
 * @property-read int|null $customers_count
 * @property-read Collection<int, \App\Models\Vendor> $defaultSeller
 * @property-read int|null $default_seller_count
 * @property-read \App\Models\Unit|null $defaultUnit
 * @property-read int|null $detail_cars_count
 * @property-read \App\Models\FormatDate $formatDate
 * @property-read \App\Models\FormatNumeric|null $format_numeric
 * @property-read int|null $import_histories_count
 * @property-read int|null $inventories_count
 * @property-read int|null $logs_count
 * @property-read int|null $navbar_rol_ids_count
 * @property-read int|null $order_categories_count
 * @property-read int|null $order_status_histories_count
 * @property-read int|null $orders_count
 * @property-read int|null $product_images_count
 * @property-read int|null $products_count
 * @property-read Collection<int, \App\Models\Vendor> $sellers
 * @property-read int|null $sellers_count
 * @property-read int|null $session_carts_count
 * @property-read int|null $status_rols_count
 * @property-read int|null $terms_of_sales_count
 * @property-read int|null $units_count
 * @property-read int|null $user_profiles_count
 * @property-read Collection<int, \App\Models\User> $users
 * @property-read int|null $users_count
 * @property-read int|null $warehouse_users_count
 * @property-read int|null $worders_list_count
 * @method static \Illuminate\Database\Eloquent\Builder|Company filter(array $input = [], $filter = null)
 * @method static \Illuminate\Database\Eloquent\Builder|Company newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Company newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Company onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder|Company paginateFilter($perPage = null, $columns = [], $pageName = 'page', $page = null)
 * @method static \Illuminate\Database\Eloquent\Builder|Company query()
 * @method static \Illuminate\Database\Eloquent\Builder|Company simplePaginateFilter($perPage = null, $columns = [], $pageName = 'page', $page = null)
 * @method static \Illuminate\Database\Eloquent\Builder|Company whereBeginsWith($column, $value, $boolean = 'and')
 * @method static \Illuminate\Database\Eloquent\Builder|Company whereCatalogsendTime($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Company whereCellNumber($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Company whereCompanyCategoryId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Company whereCompanyId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Company whereCreated($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Company whereCreatedbyCompId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Company whereCurrency($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Company whereCurrencyCodeId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Company whereDefaultUnitId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Company whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Company whereEmail($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Company whereEmailClientOnSalesmanOrder($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Company whereEmailOnSalesmanOrder($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Company whereEndsWith($column, $value, $boolean = 'and')
 * @method static \Illuminate\Database\Eloquent\Builder|Company whereFax($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Company whereFormatId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Company whereImage($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Company whereLanguageId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Company whereLike($column, $value, $boolean = 'and')
 * @method static \Illuminate\Database\Eloquent\Builder|Company whereMaxNumberOfClients($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Company whereMaxNumberOfOrders($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Company whereMaxNumberOfVendors($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Company whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Company whereOrdersendTime($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Company wherePassword($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Company wherePhoneNumber($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Company wherePlusTaxesLabel($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Company wherePublishPriceList($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Company whereRolId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Company whereSellWithInventory($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Company whereSellersIds($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Company whereSemAmarillo($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Company whereSemAmarilloDesde($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Company whereSemAmarilloHasta($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Company whereSemRed($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Company whereSemRedHasta($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Company whereSemVerde($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Company whereSemVerdeDesde($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Company whereSemVerdeHasta($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Company whereShortName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Company whereShowInventory($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Company whereShowPlusTaxesLabel($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Company whereShowSemaforoStock($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Company whereSlugUrl($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Company whereSmallImage($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Company whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Company whereTaxCondition($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Company whereTaxId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Company whereToken($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Company whereTotalPrice($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Company whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Company whereWebsite($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Company whereZipCode($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Company withTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder|Company withoutTrashed()
 * @property string|null $full_address
 * @property string|null $address
 * @property string|null $country
 * @property string|null $state
 * @property string|null $city
 * @property string|null $zip_code
 * @property string|null $zone
 * @property-read Collection<int, \App\Models\Address> $addresses
 * @property-read int|null $addresses_count
 * @property-read int|null $currencies_count
 * @method static \Database\Factories\CompanyFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Company whereAddress($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Company whereCity($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Company whereCountry($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Company whereState($value)
 * @property int|null $default_term_id
 * @property-read \App\Models\TermsOfSale|null $defaultTermOfSale
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Company whereDefaultTermId($value)
 * @mixin \Eloquent
 */
#[OA\Schema(
    schema: 'Company',
    description: 'Company model'
)]
class Company extends Model
{

    use HasFactory;
    use Filterable;
    use SoftDeletes;

    const CREATED_AT = 'created';
    protected $table = 'companies';
    protected $primaryKey = 'company_id';
    
    /**
     * The "booted" method of the model.
     */
    protected static function booted(): void
    {
        static::creating(function (Company $company): void {
            $defaults = [
                'show_plus_taxes_label' => 0,
                'show_inventory' => 0,
                'show_semaforo_stock' => 0,
                'email_client_on_salesman_order' => 1,
                'email_on_salesman_order' => 0,
                'publish_price_list' => 0,
                'sell_with_inventory' => $company->getAttributes()['sell_with_inventory'] ?? 1,
                'status' => $company->getAttributes()['status'] ?? 1,
            ];
            foreach ($defaults as $key => $value) {
                if (! array_key_exists($key, $company->getAttributes()) || $company->getAttributes()[$key] === null) {
                    $company->setAttribute($key, $value);
                }
            }
        });

        // Limpiar caché cuando se actualiza el language_id
        static::updated(function ($company) {
            if ($company->isDirty('language_id')) {
                \Illuminate\Support\Facades\Cache::forget("company_language_{$company->company_id}");
            }
        });
    }


    protected $casts = [
        'company_category_id' => 'int',
        'language_id' => 'int',
        'max_number_of_clients' => 'float',
        'max_number_of_orders' => 'float',
        'max_number_of_vendors' => 'float',
        'total_price' => 'float',
        'created' => 'datetime',
        'status' => 'int',
        'createdby_comp_id' => 'int',
        'ordersend_time' => 'int',
        'catalogsend_time' => 'int',
        'format_id' => 'int',
        'format_date_id' => 'int',
        'sell_with_inventory' => 'int',
        'rol_id' => 'int',
        'show_inventory' => 'int',
        'show_semaforo_stock' => 'int',
        'use_whatsapp' => 'int',
        'email_client_on_salesman_order' => 'int',
		'publish_price_list' => 'int',
		'show_plus_taxes_label' => 'int',
		'sem_red_hasta' => 'int',
		'sem_amarillo_desde' => 'int',
		'sem_amarillo_hasta' => 'int',
		'sem_verde_desde' => 'int',
		'sem_verde_hasta' => 'int',
        'default_unit_id' => 'int',
        'default_term_id' => 'int',
	];

    protected $hidden = [
        'password',
        'token'
    ];

    protected $fillable = [
        'company_category_id',
        'language_id',
        'name',
        'short_name',
        'email',
        'stripe_id',
        'password',
        'phone_number',
        'cell_number',
        'fax',
        'image',
        'small_image',
        'tax_condition',
        'tax_id',
        'max_number_of_clients',
        'max_number_of_orders',
        'max_number_of_vendors',
        'total_price',
        'website',
        'created',
        'status',
        'slug_url',
        'currency',
        'createdby_comp_id',
        'token',
        'ordersend_time',
        'catalogsend_time',
        'format_id',
        'format_date_id',
        'sell_with_inventory',
        'rol_id',
        'show_inventory',
        'show_semaforo_stock',
        'use_whatsapp',
        'whatsapp_instance_name',
        'whatsapp_apikey',
        'email_client_on_salesman_order',
		'publish_price_list',
		'show_plus_taxes_label',
		'plus_taxes_label',
		'sellers_ids',
    'sem_red_hasta',
		'sem_amarillo_desde',
		'sem_amarillo_hasta',
		'sem_verde_desde',
		'sem_verde_hasta',
		'sem_red',
		'sem_amarillo',
		'sem_verde',
        'default_unit_id',
        'default_term_id',
        'default_customer_group_id',
        'default_seller_id',
        'full_address',
        'address',
        'country',
        'state',
        'city',
        'zip_code',
        'zone',
	];

    public function addresses()
    {
        return $this->belongsToMany(Address::class, 'address_company', 'company_id', 'address_id')
            ->withPivot('is_primary');
    }

    public function company_category()
	{
		return $this->belongsTo(CompaniesCategory::class, 'company_category_id');
	}

	public function createdByCompany()
	{
		return $this->belongsTo(Company::class, 'createdby_comp_id');
	}

    /**
     * Moneda asociada (FK companies.currency → currency.currency_id o currency.id).
     * Nombre distinto de la columna `currency` para evitar conflicto atributo/relación en Eloquent.
     */
    public function currencyModel()
    {
        return $this->belongsTo(Currency::class, 'currency', CurrencySchemaHelper::companiesCurrencyOwnerKey());
    }

    /**
     * Monedas asociadas a la empresa (tabla currency.company_id).
     */
    public function currencies()
    {
        return $this->hasMany(Currency::class, 'company_id', 'company_id')->orderBy('name', 'ASC');
    }

    public function formatDate()
    {
        return $this->belongsTo(FormatDate::class, 'format_date_id', 'id');
    }

	public function language()
	{
		return $this->belongsTo(Language::class,'language_id','language_id');
	}

	public function rol()
	{
		return $this->belongsTo(ManagerProfiles::class, 'rol_id');
	}

	public function catalogs()
	{
		return $this->hasMany(Catalog::class, 'company_id');
	}

    public function default_catalog()
    {
        return $this->catalogs()->where('default_catalog', true)->first();
    }

	public function catalog_carts()
    {
        return $this->hasMany(CatalogCart::class);
	}

	public function catalog_sent_mails()
	{
		return $this->hasMany(CatalogSentMail::class);
	}

	public function categories()
	{
		return $this->hasMany(Category::class, 'company_id');
	}

	public function changes_apps()
	{
		return $this->hasMany(ChangesApp::class);
	}

	public function companies()
	{
		return $this->hasMany(Company::class, 'createdby_comp_id');
	}

	public function company_contacts(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
		return $this->hasMany(CompanyContact::class, 'company_id');
	}

	public function company_status_orders()
	{
		return $this->hasMany(CompanyStatusOrder::class);
	}

	public function consoleconfigs()
	{
		return $this->hasMany(Consoleconfig::class);
	}

	public function customer_groups()
	{
		return $this->hasMany(CustomerGroup::class, 'company_id');
	}

    public function default_customer_group()
    {
        return $this->belongsTo(CustomerGroup::class, 'default_customer_group_id');
    }

	public function customers()
	{
		return $this->hasMany(Customer::class, 'company_id');
	}

	public function detail_cars()
	{
		return $this->hasMany(DetailCar::class);
	}

    public function format_numeric()
    {
        return $this->hasOne(FormatNumeric::class, 'format_id', 'format_id');
    }

	public function import_histories()
	{
		return $this->hasMany(ImportHistory::class);
	}

	public function inventories()
	{
		return $this->hasMany(Inventory::class);
	}

	public function logs()
	{
		return $this->hasMany(Log::class);
	}

	public function navbar_rol_ids()
	{
		return $this->hasMany(NavbarRolId::class);
	}

	public function order_categories()
	{
		return $this->hasMany(OrderCategory::class);
	}

	public function order_status_histories()
	{
		return $this->hasMany(OrderStatusHistory::class);
	}

	public function orders()
	{
		return $this->hasMany(Order::class, 'company_id', 'company_id');
	}

	public function product_images()
	{
		return $this->hasMany(ProductImage::class);
	}

	public function products()
	{
		return $this->hasMany(Product::class, 'company_id');
	}

	public function rols()
	{
		return $this->hasOne(ManagerProfiles::class);
	}

	public function status_rols()
	{
		return $this->hasMany(StatusRol::class);
	}

	public function terms_of_sales()
	{
		return $this->hasMany(TermsOfSale::class, 'company_id');
	}

    public function defaultTermOfSale()
    {
        return $this->belongsTo(TermsOfSale::class, 'default_term_id');
    }

	public function units()
	{
		return $this->hasMany(Unit::class, 'company_id');
	}

    public function defaultUnit()
    {
        return $this->belongsTo(Unit::class, 'default_unit_id');
    }

    public function user_profiles()
	{
		return $this->hasMany(SellerProfiles::class);
    }

    public function users()
    {
        return $this->belongsToMany(User::class, 'company_user', 'company_id', 'user_id')
            ->using(\App\Models\CompanyUser::class)
            ->withPivot('type');
    }

	public function sellers()
	{
		return $this->hasMany(Vendor::class, 'company_id');
	}

    public function defaultSeller()
    {
        return $this->belongsTo(Vendor::class, 'default_seller_id');
    }

	public function warehouse_users()
	{
		return $this->hasMany(WarehouseUser::class, 'company_id');
	}

	public function worders_list()
	{
		return $this->hasMany(WordersList::class);
    }
}
