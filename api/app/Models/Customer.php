<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models;

use App\Models\Address;
use Carbon\Carbon;
use EloquentFilter\Filterable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Class Customer
 *
 * @property int $customer_id
 * @property int|null $language_id
 * @property int $company_id
 * @property string|null $name
 * @property string|null $business_name
 * @property string|null $tax_id
 * @property int|null $term_id
 * @property int|null $group_id
 * @property string|null $email
 * @property string|null $phone
 * @property string|null $cell_phone
 * @property string|null $notes
 * @property string|null $dispatch_delivery_address
 * @property string|null $dispatch_shipping_notes
 * @property int|null $catalog_emails
 * @property Carbon|null $customer_created_at
 * @property int $customer_status
 * @property string|null $cust_id_kor
 * @property int|null $id_kor
 * @property int|null $seller_assign_catalog
 * @property float|null $discount
 * @property Carbon|null $modified_at
 * @property string|null $password
 * @property float|null $balance
 * @property float|null $sales
 * @property Company $company
 * @property CustomerGroup|null $customer_group
 * @property Language|null $language
 * @property TermsOfSale|null $terms_of_sale
 * @property Collection|CatalogCart[] $catalog_carts
 * @property Collection|CatalogRequest[] $catalog_requests
 * @property Collection|DetailCar[] $detail_cars
 * @property Collection|Order[] $orders
 * @property Collection|SessionCart[] $session_carts
 * @property WordersList $worders_list
 * @package App\Models
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @property-read Collection<int, \App\Models\CatalogSentMail> $catalogSentMails
 * @property-read int|null $catalog_sent_mails_count
 * @property-read int|null $catalog_carts_count
 * @property-read int|null $catalog_requests_count
 * @property-read int|null $detail_cars_count
 * @property-read int|null $orders_count
 * @property-read Collection<int, \App\Models\Vendor> $sellers
 * @property-read int|null $sellers_count
 * @property-read int|null $worders_list_count
 * @method static \Database\Factories\CustomerFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder|Customer filter(array $input = [], $filter = null)
 * @method static \Illuminate\Database\Eloquent\Builder|Customer newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Customer newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Customer onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder|Customer paginateFilter($perPage = null, $columns = [], $pageName = 'page', $page = null)
 * @method static \Illuminate\Database\Eloquent\Builder|Customer query()
 * @method static \Illuminate\Database\Eloquent\Builder|Customer simplePaginateFilter($perPage = null, $columns = [], $pageName = 'page', $page = null)
 * @method static \Illuminate\Database\Eloquent\Builder|Customer whereAssignCatalog($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Customer whereBalance($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Customer whereBeginsWith($column, $value, $boolean = 'and')
 * @method static \Illuminate\Database\Eloquent\Builder|Customer whereBusinessName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Customer whereCatalogEmails($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Customer whereCellPhone($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Customer whereCommercialAddress($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Customer whereCommercialCity($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Customer whereCommercialCountry($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Customer whereCommercialDeliveryAddress($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Customer whereCommercialState($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Customer whereCommercialZipCode($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Customer whereCommercialZone($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Customer whereCompanyId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Customer whereCustIdKor($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Customer whereCustomerCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Customer whereCustomerId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Customer whereCustomerStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Customer whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Customer whereDiscount($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Customer whereDispatchAddress($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Customer whereDispatchCity($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Customer whereDispatchCountry($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Customer whereDispatchDeliveryAddress($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Customer whereDispatchShippingNotes($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Customer whereDispatchState($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Customer whereDispatchZipCode($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Customer whereDispatchZone($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Customer whereEmail($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Customer whereEndsWith($column, $value, $boolean = 'and')
 * @method static \Illuminate\Database\Eloquent\Builder|Customer whereGroupId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Customer whereIdKor($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Customer whereLanguageId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Customer whereLike($column, $value, $boolean = 'and')
 * @method static \Illuminate\Database\Eloquent\Builder|Customer whereModifiedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Customer whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Customer whereNotes($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Customer wherePassword($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Customer wherePhone($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Customer whereSales($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Customer whereTaxId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Customer whereTermId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Customer whereUserId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Customer withTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder|Customer withoutTrashed()
 * @property string|null $commercial_address
 * @property string|null $commercial_delivery_address
 * @property string|null $commercial_country
 * @property string|null $commercial_state
 * @property string|null $commercial_city
 * @property string|null $commercial_zone
 * @property string|null $commercial_zip_code
 * @property string|null $dispatch_address
 * @property string|null $dispatch_country
 * @property string|null $dispatch_state
 * @property string|null $dispatch_city
 * @property string|null $dispatch_zone
 * @property string|null $dispatch_zip_code
 * @property-read Collection<int, Address> $addresses
 * @property-read int|null $addresses_count
 * @property-read Collection<int, Address> $commercialAddresses
 * @property-read int|null $commercial_addresses_count
 * @property-read Collection<int, Address> $commercialDeliveryAddresses
 * @property-read int|null $commercial_delivery_addresses_count
 * @property-read Collection<int, Address> $dispatchAddresses
 * @property-read int|null $dispatch_addresses_count
 * @property-read \App\Models\User|null $user
 * @property-read Collection<int, Address> $primaryAddresses
 * @property-read int|null $primary_addresses_count
 * @mixin \Eloquent
 */
#[OA\Schema(
    schema: 'Customer',
    description: 'Customer model'
)]
class Customer extends Model
{

    use HasFactory;
    use Filterable;

    use SoftDeletes;

    const CREATED_AT = 'customer_created_at';
    const UPDATED_AT = 'modified_at';
    protected $table = 'customers';
    protected $primaryKey = 'customer_id';


    protected $casts = [
        'language_id' => 'int',
        'company_id' => 'int',
        'term_id' => 'int',
        'group_id' => 'int',
        'catalog_emails' => 'int',
        'customer_created_at' => 'datetime',
        'customer_status' => 'int',
        'id_kor' => 'int',
        'seller_assign_catalog' => 'int',
        'discount' => 'float',
        'modified_at' => 'datetime',
        'balance' => 'float',
        'sales' => 'float'
    ];

    protected $hidden = [
        'password'
    ];

    protected $fillable = [
//        'language_id',
        'company_id',
        'name',
        'business_name',
        'tax_id',
        'term_id',
        'group_id',
        'catalog_emails',
        'email',
        'phone',
        'cell_phone',
        'notes',
        'commercial_address',
        'commercial_delivery_address',
        'commercial_country',
        'commercial_state',
        'commercial_city',
        'commercial_zone',
        'commercial_zip_code',
        'dispatch_address',
        'dispatch_delivery_address',
        'dispatch_country',
        'dispatch_state',
        'dispatch_city',
        'dispatch_zone',
        'dispatch_zip_code',
        'dispatch_shipping_notes',
        'customer_status',
        'seller_assign_catalog',
        'discount',
        'cust_id_kor',
        'id_kor',
//        'password',
//        'balance',
//        'sales'
    ];

    /**
     * Mutator para convertir name a mayúsculas al guardar
     */
    public function setNameAttribute($value)
    {
        $this->attributes['name'] = $value !== null ? mb_strtoupper($value, 'UTF-8') : null;
    }

    /**
     * Mutator para convertir business_name a mayúsculas al guardar
     */
    public function setBusinessNameAttribute($value)
    {
        $this->attributes['business_name'] = $value !== null ? mb_strtoupper($value, 'UTF-8') : null;
    }

    /**
     * Accessor para convertir name a mayúsculas al leer
     */
    public function getNameAttribute($value)
    {
        return $value !== null ? mb_strtoupper($value, 'UTF-8') : null;
    }

    /**
     * Accessor para convertir business_name a mayúsculas al leer
     */
    public function getBusinessNameAttribute($value)
    {
        return $value !== null ? mb_strtoupper($value, 'UTF-8') : null;
    }

    public function addresses(): \Illuminate\Database\Eloquent\Relations\BelongsToMany
    {
        return $this->belongsToMany(Address::class, 'address_customer', 'customer_id', 'address_id')
            ->withPivot('is_primary', 'type')
            ->wherePivotNull('deleted_at');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
	public function company()
	{
		return $this->belongsTo(Company::class,'company_id','company_id');
	}

	public function customer_group()
	{
		return $this->belongsTo(CustomerGroup::class, 'group_id', 'group_id');
	}

	public function language()
	{
		return $this->belongsTo(Language::class);
	}

	public function terms_of_sale()
	{
		return $this->belongsTo(TermsOfSale::class, 'term_id');
	}

	public function catalog_carts()
	{
		return $this->hasMany(CatalogCart::class);
	}

	public function catalog_requests()
	{
		return $this->hasMany(CatalogRequest::class, 'customer_id', 'customer_id');
	}

    public function catalogSentMails()
    {
        return $this->hasMany(CatalogSentMail::class, 'customerIds', 'customer_id');
    }

	public function detail_cars()
	{
		return $this->hasMany(DetailCar::class);
	}

	public function orders()
	{
		return $this->hasMany(Order::class, 'customer_id', 'customer_id');
	}

	public function worders_list()
	{
		return $this->hasMany(WordersList::class);
	}

    public function sellers()
    {
        // El pivot usa soft deletes; sin filtrar, detach/exists pueden verse filas obsoletas y parecer que no se quitó el vendedor.
        return $this->belongsToMany(Vendor::class, 'customer_seller', 'customer_id', 'seller_id')
            ->wherePivotNull('deleted_at');
    }

    public function sellerAssignCatalog(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Vendor::class, 'seller_assign_catalog', 'id');
    }


    public function primaryCommercialAddress()
    {
        return $this->addresses()
            ->wherePivot('type', 'commercial')
            ->wherePivot('is_primary', true)
            ->first();
    }

    public function primaryDispatchAddress()
    {
        return $this->addresses()
            ->wherePivot('type', 'dispatch')
            ->wherePivot('is_primary', true)
            ->first();
    }

    public function primaryCommercialDeliveryAddress()
    {
        return $this->addresses()
            ->wherePivot('type', 'commercial_delivery')
            ->wherePivot('is_primary', true)
            ->first();
    }

    public function primaryAddresses(): \Illuminate\Database\Eloquent\Relations\BelongsToMany
    {
        return $this->addresses()->wherePivot('is_primary', true);
    }

    public function commercialAddresses(): \Illuminate\Database\Eloquent\Relations\BelongsToMany
    {
        return $this->addresses()->wherePivot('type', 'commercial');
    }

    public function commercialDeliveryAddresses(): \Illuminate\Database\Eloquent\Relations\BelongsToMany
    {
        return $this->addresses()->wherePivot('type', 'commercial_delivery');
    }

    public function dispatchAddresses(): \Illuminate\Database\Eloquent\Relations\BelongsToMany
    {
        return $this->addresses()->wherePivot('type', 'dispatch');
    }

    public function scopeWithoutSeller($query)
    {
        return $query->whereDoesntHave('sellers');
    }

    public function users(): \Illuminate\Database\Eloquent\Relations\BelongsToMany
    {
        return $this->belongsToMany(
            User::class,
            'customer_user',
            'customer_id',
            'user_id'
        );
    }

    public function emails(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(CustomerEmail::class, 'customer_id', 'customer_id');
    }
}
