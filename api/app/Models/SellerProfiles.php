<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models;

use Carbon\Carbon;
use EloquentFilter\Filterable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Class SellerProfiles
 *
 * @property int $profile_id
 * @property string|null $profile_name
 * @property int|null $company_id
 * @property int $order_discount
 * @property string|null $order_disc_price
 * @property int $order_net_discount
 * @property string|null $order_net_disc_price
 * @property int $can_change_price
 * @property int $can_send_catalog
 * @property int $can_create_customer
 * @property Carbon|null $created
 * @property int|null $status
 * @property int|null $default
 * @property string|null $deleted_at
 * @property Company|null $company
 * @package App\Models
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\CompanyUser> $companyUsers
 * @property-read int|null $company_users_count
 * @method static \Database\Factories\SellerProfilesFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder|SellerProfiles filter(array $input = [], $filter = null)
 * @method static \Illuminate\Database\Eloquent\Builder|SellerProfiles newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|SellerProfiles newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|SellerProfiles onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder|SellerProfiles paginateFilter($perPage = null, $columns = [], $pageName = 'page', $page = null)
 * @method static \Illuminate\Database\Eloquent\Builder|SellerProfiles query()
 * @method static \Illuminate\Database\Eloquent\Builder|SellerProfiles simplePaginateFilter($perPage = null, $columns = [], $pageName = 'page', $page = null)
 * @method static \Illuminate\Database\Eloquent\Builder|SellerProfiles whereBeginsWith($column, $value, $boolean = 'and')
 * @method static \Illuminate\Database\Eloquent\Builder|SellerProfiles whereCanChangePrice($value)
 * @method static \Illuminate\Database\Eloquent\Builder|SellerProfiles whereCanCreateCustomer($value)
 * @method static \Illuminate\Database\Eloquent\Builder|SellerProfiles whereCanSendCatalog($value)
 * @method static \Illuminate\Database\Eloquent\Builder|SellerProfiles whereCompanyId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|SellerProfiles whereCreated($value)
 * @method static \Illuminate\Database\Eloquent\Builder|SellerProfiles whereDefault($value)
 * @method static \Illuminate\Database\Eloquent\Builder|SellerProfiles whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|SellerProfiles whereEndsWith($column, $value, $boolean = 'and')
 * @method static \Illuminate\Database\Eloquent\Builder|SellerProfiles whereLike($column, $value, $boolean = 'and')
 * @method static \Illuminate\Database\Eloquent\Builder|SellerProfiles whereOrderDiscPrice($value)
 * @method static \Illuminate\Database\Eloquent\Builder|SellerProfiles whereOrderDiscount($value)
 * @method static \Illuminate\Database\Eloquent\Builder|SellerProfiles whereOrderNetDiscPrice($value)
 * @method static \Illuminate\Database\Eloquent\Builder|SellerProfiles whereOrderNetDiscount($value)
 * @method static \Illuminate\Database\Eloquent\Builder|SellerProfiles whereProfileId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|SellerProfiles whereProfileName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|SellerProfiles whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder|SellerProfiles whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|SellerProfiles withTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder|SellerProfiles withoutTrashed()
 * @mixin \Eloquent
 */
#[OA\Schema(
    schema: 'Userprofile',
    description: 'SellerProfiles model'
)]
class SellerProfiles extends Model
{

    use HasFactory;
    use Filterable;
    use SoftDeletes;

    const CREATED_AT = 'created';
    protected $table = 'seller_profiles';
    protected $primaryKey = 'profile_id';


    protected $casts = [
        'company_id' => 'int',
        'order_discount' => 'int',
        'order_net_discount' => 'int',
        'can_change_price' => 'int',
        'can_send_catalog' => 'int',
        'can_create_customer' => 'int',
        'created' => 'datetime',
        'status' => 'int',
        'default' => 'int'
    ];

    protected $fillable = [
        'profile_name',
        'company_id',
        'order_discount',
        'order_disc_price',
        'order_net_discount',
        'order_net_disc_price',
        'can_change_price',
        'can_send_catalog',
        'can_create_customer',
        'created',
        'status',
        'default'
    ];

	public function company()
	{
		return $this->belongsTo(Company::class);
	}

    public function companyUsers(): MorphMany
    {
        return $this->morphMany(CompanyUser::class, 'profileable');
    }
}
