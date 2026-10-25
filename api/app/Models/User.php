<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models;

use App\Enums\CompanyUserTypes;
use Carbon\Carbon;
use EloquentFilter\Filterable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Laravel\Sanctum\HasApiTokens;

/**
 * Class User
 *
 * @property int $id
 * @property string $name
 * @property string $email
 * @property Carbon|null $email_verified_at
 * @property string $password
 * @property string|null $password_plain
 * @property string $image
 * @property string $phone
 * @property string $cellphone
 * @property string $fax
 * @property string|null $two_factor_secret
 * @property string|null $two_factor_recovery_codes
 * @property Carbon|null $two_factor_confirmed_at
 * @property string|null $remember_token
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Collection|CatalogCart[] $catalog_carts
 * @property Collection|CatalogRequest[] $catalog_requests
 * @property Collection|ChangesApp[] $changes_apps
 * @property Collection|CustomerGroup[] $customer_groups
 * @property Collection|Key[] $keys
 * @property Collection|Log[] $logs
 * @property Collection|Company[] $companies
 * @property Collection|OrderStatusHistory[] $order_status_histories
 * @property Collection|TermsOfSale[] $terms_of_sales
 * @property WordersList $worders_list
 * @package App\Models
 * @property int $language_id
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @property-read \App\Models\Admin|null $admin
 * @property-read int|null $catalog_carts_count
 * @property-read int|null $catalog_requests_count
 * @property-read int|null $changes_apps_count
 * @property-read int|null $companies_count
 * @property-read \App\Models\Customer|null $customer
 * @property-read int|null $customer_groups_count
 * @property-read int|null $keys_count
 * @property-read \App\Models\Language|null $language
 * @property-read int|null $logs_count
 * @property-read int|null $order_status_histories_count
 * @property-read Collection<int, \App\Models\CompanyUser> $roles
 * @property-read int|null $roles_count
 * @property-read \App\Models\Vendor|null $seller
 * @property-read int|null $terms_of_sales_count
 * @property-read Collection<int, \Laravel\Sanctum\PersonalAccessToken> $tokens
 * @property-read int|null $tokens_count
 * @property-read int|null $worders_list_count
 * @method static \Database\Factories\UserFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder|User filter(array $input = [], $filter = null)
 * @method static \Illuminate\Database\Eloquent\Builder|User newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|User newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|User onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder|User paginateFilter($perPage = null, $columns = [], $pageName = 'page', $page = null)
 * @method static \Illuminate\Database\Eloquent\Builder|User query()
 * @method static \Illuminate\Database\Eloquent\Builder|User simplePaginateFilter($perPage = null, $columns = [], $pageName = 'page', $page = null)
 * @method static \Illuminate\Database\Eloquent\Builder|User whereBeginsWith($column, $value, $boolean = 'and')
 * @method static \Illuminate\Database\Eloquent\Builder|User whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|User whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|User whereEmail($value)
 * @method static \Illuminate\Database\Eloquent\Builder|User whereEmailVerifiedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|User whereEndsWith($column, $value, $boolean = 'and')
 * @method static \Illuminate\Database\Eloquent\Builder|User whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|User whereLanguageId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|User whereLike($column, $value, $boolean = 'and')
 * @method static \Illuminate\Database\Eloquent\Builder|User whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|User wherePassword($value)
 * @method static \Illuminate\Database\Eloquent\Builder|User whereRememberToken($value)
 * @method static \Illuminate\Database\Eloquent\Builder|User whereTwoFactorConfirmedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|User whereTwoFactorRecoveryCodes($value)
 * @method static \Illuminate\Database\Eloquent\Builder|User whereTwoFactorSecret($value)
 * @method static \Illuminate\Database\Eloquent\Builder|User whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|User withTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder|User withoutTrashed()
 * @mixin \Eloquent
 */
class User extends Authenticatable
{
    use SoftDeletes;
    use HasApiTokens;
    use HasFactory;
    use Filterable;


    protected $table = 'users';

    protected $casts = [
        'email_verified_at' => 'datetime',
        'two_factor_confirmed_at' => 'datetime'
    ];

    protected $hidden = [
        'password',
        'two_factor_secret',
        'remember_token',
    ];

    protected $fillable = [
        'name',
        'first_name',
        'last_name',
        'email',
        'password',
        'password_plain',
        'image',
        'phone',
        'cellphone',
        'fax',
        'address',
        'city',
        'state',
        'zip_code',
        'remember_token'
    ];

    /**
     * Determines if the user is a fullvendor administrator
     * @return bool
     */
    public function isAdmin(): bool
    {
        return $this->admin()
            ->where(function ($q) {
                $q->whereNull('status')->orWhere('status', '!=', 0);
            })
            ->exists();
    }

    public function companiesForRole($role) {
        return $this->companies()->wherePivot('type',$role);
    }

    /**
     * Determines if the user has $type capabilities
     * @param $type
     * @param int $companyId If present determines whether the user is a user ot the $type in the given company
     * @return bool
     */
    protected function isCompanyUser($type,$companyId=null): bool {
        if (!isset($companyId)) {
            $companyId = request()->input('company_id');
        }
        $companies = $this->companiesForRole($type)->get();
        foreach ($companies as $company) {
            if (!isset($companyId) || $company->company_id == $companyId) {
                return true;
            }
        }
        return false;
    }

    /**
     * Determines if the user has manager capabilities
     * @param int $companyId If present determines whether the user is manager of the given company
     * @return bool
     */
    public function isManager($companyId = null): bool
    {
        return $this->isCompanyUser('manager', $companyId);
    }

    /**
     * Determines if the user is a customer
     * @param int $companyId If present determines whether  the user is manager of the given company
     * @return bool
     */
    public function isCustomer($companyId = null): bool {
        return $this->isCompanyUser('customer', $companyId);
    }

    /**
     * Determines if the user has manager capabilities
     * @param int $companyId If present determines whether the user is manager of the given company
     * @return bool
     */
    public function isWarehouse($companyId = null): bool {
        return $this->isCompanyUser('warehouse', $companyId);
    }

    /**
     * Determines if the user has seller capabilities
     * @param $companyId
     * @return bool
     */
    public function isSeller($companyId=null): bool
    {
        return $this->isCompanyUser('seller', $companyId);
    }


    public function admin(): HasOne
    {
        return $this->hasOne(Admin::class, 'user_id');
    }

    public function customers(): \Illuminate\Database\Eloquent\Relations\BelongsToMany
    {
        return $this->belongsToMany(
            Customer::class,
            'customer_user',
            'user_id',
            'customer_id'
        );
    }

    public function customerByCompany(int $companyId): ?Customer
    {
        return $this->customers()->where('company_id', $companyId)->first();
    }

    public function catalog_carts()
    {
        return $this->hasMany(CatalogCart::class);
    }

    public function catalog_requests()
    {
        return $this->hasMany(CatalogRequest::class);
    }

    public function changes_apps()
    {
        return $this->hasMany(ChangesApp::class);
    }

    public function customer_groups()
    {
        return $this->hasMany(CustomerGroup::class);
    }

    public function companies()
    {
        return $this->belongsToMany(Company::class, 'company_user', 'user_id', 'company_id')
            ->using(\App\Models\CompanyUser::class)
            ->withPivot('type','profileable_id','profileable_type');
    }

    public function companyUsers()
    {
        return $this->hasMany(CompanyUser::class, 'user_id', 'id');
    }

    public function keys()
    {
        return $this->hasMany(Key::class);
    }

    public function language()
    {
        return $this->belongsTo(Language::class);
    }

    public function logs()
    {
        return $this->hasMany(Log::class, 'user_id', 'id');
    }

    public function order_status_histories()
    {
        return $this->hasMany(OrderStatusHistory::class);
    }

    public function terms_of_sales()
    {
        return $this->hasMany(TermsOfSale::class);
    }

    public function worders_list()
    {
        return $this->hasMany(WordersList::class);
    }

    public function sellers(): HasMany
    {
        return $this->hasMany(Vendor::class, 'user_id', 'id');
    }

    public function sellerByCompany(int $companyId): ?Vendor
    {
        return $this->sellers()->where('company_id', $companyId)->first();
    }

    public function sellerCompanyUser()
    {
        return $this->hasOne(CompanyUser::class)->where('type', CompanyUserTypes::Seller);
    }

    public function roles(): HasMany
    {
        return $this->hasMany(CompanyUser::class, 'user_id');
    }

    public function rolesByCompany($companyId)
    {
        return $this->roles()
            ->where('company_id', $companyId)
            ->pluck('type')
            ->unique()
            ->toArray();
    }

    public function abilities(string $role): array
    {
        //TODO: Get it from GATES or MODEL?
        $roleAbilities = [
            'admin' => ['manage_users', 'edit_settings', 'view_logs'],
            'manager' => ['manage_users', 'edit_settings'],
            'seller' => ['create_orders', 'view_reports'],
            'customer' => ['view_products'],
            'warehouse' => ['manage_inventory'],
        ];

        return $roleAbilities[$role] ?? [];
    }

    public function scopeForCompany($query, $companyId)
    {
        return $query->whereHas('companyUsers', function ($q) use ($companyId) {
            $q->where('company_id', $companyId);
            })
            ->with([
                'companyUsers' => function ($q) use ($companyId) {
                    $q->where('company_id', $companyId)
                        ->with('profileable');
                }
            ]);
    }

    public function warehouses(): \Illuminate\Database\Eloquent\Relations\BelongsToMany
    {
        return $this->belongsToMany(Warehouse::class, 'warehouse_users', 'user_id', 'warehouse_id')
            ->withPivot('company_id');
    }

    public function warehouseByCompany(int $companyId)
    {
        return $this->warehouses()->where('warehouses.company_id', $companyId)->first();
    }

    /**
     * Acceso al panel company para una compañía concreta (company_id de sesión MVC).
     * Incluye pivote company_user y cuenta empresa legacy (users.email = companies.email).
     */
    public function canAccessCompany(int $companyId): bool
    {
        if ($companyId <= 0) {
            return false;
        }

        if ($this->isAdmin()) {
            return true;
        }

        if ($this->companies()->where('companies.company_id', $companyId)->exists()) {
            return true;
        }

        $email = strtolower(trim((string) $this->email));
        if ($email === '') {
            return false;
        }

        return Company::query()
            ->whereNull('deleted_at')
            ->whereRaw('LOWER(email) = ?', [$email])
            ->where(function ($q) use ($companyId) {
                $q->where('company_id', $companyId)
                    ->orWhere('createdby_comp_id', $companyId);
            })
            ->exists();
    }

}
