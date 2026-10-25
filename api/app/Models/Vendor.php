<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models;

use Carbon\Carbon;
use EloquentFilter\Filterable;
use FontLib\TrueType\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Class Vendor
 *
 * @property int $user_id
 * @property int|null $company_id
 * @property int|null $profile
 * @property string|null $phone_number
 * @property string|null $cell_number
 * @property string|null $fax
 * @property string|null $profile_image
 * @property int|null $email_verification
 * @property Carbon|null $created
 * @property Carbon|null $updated_at
 * @property int|null $status
 * @property int|null $id_kor
 * @property string|null $token
 * @property int|null $all_customers
 * @property int $proforma
 * @property Company|null $company
 * @property Collection $customers
 * @package App\Models
 * @property int $id
 * @property int|null $email_on_salesman_order
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @property-read int|null $customers_count
 * @property-read \App\Models\User|null $user
 * @method static \Database\Factories\VendorFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder|Vendor filter(array $input = [], $filter = null)
 * @method static \Illuminate\Database\Eloquent\Builder|Vendor newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Vendor newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Vendor onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder|Vendor paginateFilter($perPage = null, $columns = [], $pageName = 'page', $page = null)
 * @method static \Illuminate\Database\Eloquent\Builder|Vendor query()
 * @method static \Illuminate\Database\Eloquent\Builder|Vendor simplePaginateFilter($perPage = null, $columns = [], $pageName = 'page', $page = null)
 * @method static \Illuminate\Database\Eloquent\Builder|Vendor whereAddCustomer($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Vendor whereAllCustomers($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Vendor whereBeginsWith($column, $value, $boolean = 'and')
 * @method static \Illuminate\Database\Eloquent\Builder|Vendor whereCellNumber($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Vendor whereCompanyId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Vendor whereCreated($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Vendor whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Vendor whereEmailOnSalesmanOrder($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Vendor whereEndsWith($column, $value, $boolean = 'and')
 * @method static \Illuminate\Database\Eloquent\Builder|Vendor whereFax($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Vendor whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Vendor whereLike($column, $value, $boolean = 'and')
 * @method static \Illuminate\Database\Eloquent\Builder|Vendor whereOtp($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Vendor wherePhoneNumber($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Vendor whereProforma($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Vendor whereSendCatalog($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Vendor whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Vendor whereUniqueId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Vendor whereUpdateCustomer($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Vendor whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Vendor whereUserId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Vendor withTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder|Vendor withoutTrashed()
 * @mixin \Eloquent
 */
#[OA\Schema(
    schema: 'Vendor',
    description: 'Vendor model'
)]
class Vendor extends Model
{

    use HasFactory;
    use Filterable;

    use SoftDeletes;

    const CREATED_AT = 'created';
    const UPDATED_AT = 'updated_at';
    protected $table = 'sellers';
    protected $primaryKey = 'id';


    protected $casts = [
        'company_id' => 'int',
        'profile' => 'int',
        'email_verification' => 'int',
        'created' => 'datetime',
        'status' => 'int',
        'id_kor' => 'int',
        'all_customers' => 'int',
        'proforma' => 'int',
        'user_id' => 'int'
    ];

    protected $hidden = [
        'password',
        'token'
    ];

    protected $fillable = [
        'phone_number',
        'cell_number',
        'fax',
        'status',
        'all_customers',
        'proforma',
        'user_id',
        'company_id',
        'email_on_salesman_order',
        // Legacy en algunos hosts de producción (se ignoran si la columna ya no existe).
        'add_customer',
        'update_customer',
        'send_catalog',
    ];

    protected $attributes = [
        'all_customers' => 0,
        'proforma' => 0,
        'email_on_salesman_order' => 0,
    ];

    protected static function booted(): void
    {
        static::creating(function (Vendor $vendor) {
            // Hosts con columnas legacy NOT NULL sin default (antes de schema:sync / drop).
            foreach (['add_customer', 'update_customer', 'send_catalog'] as $legacyCol) {
                if (\Illuminate\Support\Facades\Schema::hasColumn('sellers', $legacyCol)
                    && $vendor->getAttribute($legacyCol) === null
                ) {
                    $vendor->setAttribute($legacyCol, 0);
                }
            }
            // Si la columna ya no existe, quitar del modelo para no enviarlas en el INSERT.
            foreach (['add_customer', 'update_customer', 'send_catalog'] as $legacyCol) {
                if (! \Illuminate\Support\Facades\Schema::hasColumn('sellers', $legacyCol)) {
                    $vendor->offsetUnset($legacyCol);
                }
            }
        });
    }

    protected $appends = [
        'isDefault'
    ];

	public function company()
	{
		return $this->belongsTo(Company::class, 'company_id', 'company_id');
	}

    public function customers(): \Illuminate\Database\Eloquent\Relations\BelongsToMany
    {
        return $this->belongsToMany(Customer::class, 'customer_seller', 'seller_id', 'customer_id')
            ->wherePivotNull('deleted_at');
    }

    public function orders()
    {
        return $this->hasMany(Order::class, 'seller_id', 'id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function getIsDefaultAttribute(): bool
    {
        // Evitar N+1 en listados de clientes: no consultar company si no está cargada.
        if (! $this->relationLoaded('company') || ! $this->company) {
            return false;
        }

        return (int) $this->company->default_seller_id === (int) $this->getKey();
    }
}
